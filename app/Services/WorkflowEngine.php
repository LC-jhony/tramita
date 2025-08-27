<?php

namespace App\Services;

use App\Models\Document;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\User;
use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class WorkflowEngine
{
    /**
     * Start a new workflow for a document
     */
    public function startWorkflow(Document $document, WorkflowTemplate $template): WorkflowInstance
    {
        return DB::transaction(function () use ($document, $template) {
            // Validate template
            $errors = $template->validateConfig();
            if (!empty($errors)) {
                throw new \Exception('Template inválido: ' . implode(', ', $errors));
            }

            // Create workflow instance
            $workflow = WorkflowInstance::create([
                'document_id' => $document->id,
                'template_id' => $template->id,
                'current_step' => $template->getFirstStep(),
                'status' => 'active',
                'data' => [],
                'started_at' => now(),
                'estimated_completion' => $this->calculateEstimatedCompletion($template),
                'priority' => $document->priority ?? 'normal'
            ]);

            // Create first step
            $firstStep = collect($template->getSteps())->firstWhere('id', $template->getFirstStep());
            if ($firstStep) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'step_id' => $firstStep['id'],
                    'name' => $firstStep['name'],
                    'type' => $firstStep['type'],
                    'status' => 'active',
                    'assigned_to' => $this->determineAssignee($firstStep, $document),
                    'started_at' => now(),
                    'due_date' => $this->calculateStepDueDate($firstStep),
                    'input_data' => []
                ]);
            }

            // Update document
            $document->update([
                'status' => 'in_process',
                'workflow_id' => $workflow->id
            ]);

            Log::info("Workflow started", [
                'document_id' => $document->id,
                'workflow_id' => $workflow->id,
                'template' => $template->name
            ]);

            return $workflow;
        });
    }

    /**
     * Advance workflow to next step
     */
    public function advanceWorkflow(WorkflowInstance $workflow, string $targetStepId, array $data = []): bool
    {
        return DB::transaction(function () use ($workflow, $targetStepId, $data) {
            // Validate transition
            if (!$this->canAdvanceToStep($workflow, $targetStepId)) {
                throw new \Exception("No se puede avanzar al paso '{$targetStepId}'");
            }

            // Apply business rules
            $this->applyBusinessRules($workflow, $targetStepId, $data);

            // Complete current step
            $currentStep = $workflow->currentStepInstance();
            if ($currentStep) {
                $currentStep->complete($data['output_data'] ?? [], $data['notes'] ?? null);
            }

            // Get target step configuration
            $targetStepConfig = collect($workflow->template->getSteps())
                ->firstWhere('id', $targetStepId);

            if (!$targetStepConfig) {
                throw new \Exception("Paso '{$targetStepId}' no encontrado en el template");
            }

            // Create new step instance
            $newStep = WorkflowStep::create([
                'workflow_id' => $workflow->id,
                'step_id' => $targetStepId,
                'name' => $targetStepConfig['name'],
                'type' => $targetStepConfig['type'],
                'status' => 'active',
                'assigned_to' => $this->determineAssignee($targetStepConfig, $workflow->document),
                'started_at' => now(),
                'due_date' => $this->calculateStepDueDate($targetStepConfig),
                'input_data' => $data['input_data'] ?? []
            ]);

            // Update workflow
            $workflow->update([
                'current_step' => $targetStepId,
                'data' => array_merge($workflow->data ?? [], $data['workflow_data'] ?? [])
            ]);

            // Check if workflow is complete
            if ($targetStepConfig['type'] === 'end') {
                $this->completeWorkflow($workflow);
            }

            // Auto-advance if configured
            if ($targetStepConfig['auto_complete'] ?? false) {
                $this->autoAdvanceStep($workflow, $newStep);
            }

            Log::info("Workflow advanced", [
                'workflow_id' => $workflow->id,
                'from_step' => $currentStep?->step_id,
                'to_step' => $targetStepId
            ]);

            return true;
        });
    }

    /**
     * Complete workflow
     */
    public function completeWorkflow(WorkflowInstance $workflow): void
    {
        $workflow->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $workflow->document->update([
            'status' => 'completed'
        ]);

        // Trigger completion events
        $this->triggerWorkflowEvents($workflow, 'completed');

        Log::info("Workflow completed", [
            'workflow_id' => $workflow->id,
            'document_id' => $workflow->document_id
        ]);
    }

    /**
     * Reject workflow
     */
    public function rejectWorkflow(WorkflowInstance $workflow, string $reason): void
    {
        $workflow->update([
            'status' => 'rejected',
            'completed_at' => now(),
            'data' => array_merge($workflow->data ?? [], ['rejection_reason' => $reason])
        ]);

        $workflow->document->update([
            'status' => 'rejected'
        ]);

        // Complete current step as rejected
        $currentStep = $workflow->currentStepInstance();
        if ($currentStep) {
            $currentStep->reject($reason);
        }

        $this->triggerWorkflowEvents($workflow, 'rejected');

        Log::info("Workflow rejected", [
            'workflow_id' => $workflow->id,
            'reason' => $reason
        ]);
    }

    /**
     * Check if workflow can advance to specific step
     */
    public function canAdvanceToStep(WorkflowInstance $workflow, string $targetStepId): bool
    {
        $nextSteps = collect($workflow->getNextSteps());
        return $nextSteps->contains('id', $targetStepId);
    }

    /**
     * Get available actions for current step
     */
    public function getAvailableActions(WorkflowInstance $workflow): array
    {
        $currentStep = $workflow->currentStepInstance();
        if (!$currentStep || $currentStep->status !== 'active') {
            return [];
        }

        $actions = ['complete', 'reject', 'pause'];
        
        // Add step-specific actions
        $stepConfig = collect($workflow->template->getSteps())
            ->firstWhere('id', $workflow->current_step);

        if ($stepConfig) {
            switch ($stepConfig['type']) {
                case 'approval':
                    $actions = array_merge($actions, ['approve', 'deny']);
                    break;
                case 'validation':
                    $actions = array_merge($actions, ['validate', 'request_correction']);
                    break;
                case 'inspection':
                    $actions = array_merge($actions, ['pass_inspection', 'fail_inspection']);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Execute workflow action
     */
    public function executeAction(WorkflowInstance $workflow, string $action, array $data = []): bool
    {
        $currentStep = $workflow->currentStepInstance();
        if (!$currentStep) {
            return false;
        }

        return match($action) {
            'complete' => $this->completeCurrentStep($workflow, $data),
            'reject' => $this->rejectCurrentStep($workflow, $data),
            'pause' => $this->pauseWorkflow($workflow, $data),
            'approve' => $this->approveStep($workflow, $data),
            'deny' => $this->denyStep($workflow, $data),
            'validate' => $this->validateStep($workflow, $data),
            'request_correction' => $this->requestCorrection($workflow, $data),
            'pass_inspection' => $this->passInspection($workflow, $data),
            'fail_inspection' => $this->failInspection($workflow, $data),
            default => false
        };
    }

    /**
     * Get workflow analytics
     */
    public function getWorkflowAnalytics(WorkflowTemplate $template, array $filters = []): array
    {
        $query = WorkflowInstance::where('template_id', $template->id);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('started_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('started_at', '<=', $filters['date_to']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $workflows = $query->with(['steps', 'document'])->get();

        return [
            'total_workflows' => $workflows->count(),
            'completed_workflows' => $workflows->where('status', 'completed')->count(),
            'active_workflows' => $workflows->where('status', 'active')->count(),
            'rejected_workflows' => $workflows->where('status', 'rejected')->count(),
            'average_completion_time' => $this->calculateAverageCompletionTime($workflows),
            'bottleneck_steps' => $this->identifyBottleneckSteps($workflows),
            'completion_rate' => $this->calculateCompletionRate($workflows),
            'overdue_workflows' => $workflows->filter->isOverdue()->count(),
            'step_performance' => $this->analyzeStepPerformance($workflows),
            'monthly_trends' => $this->getMonthlyTrends($workflows)
        ];
    }

    /**
     * Auto-advance step if configured
     */
    protected function autoAdvanceStep(WorkflowInstance $workflow, WorkflowStep $step): void
    {
        $stepConfig = collect($workflow->template->getSteps())
            ->firstWhere('id', $step->step_id);

        if ($stepConfig['auto_complete'] ?? false) {
            $step->complete(['auto_completed' => true], 'Auto-completado por el sistema');
            
            $nextSteps = $workflow->getNextSteps();
            if (!empty($nextSteps)) {
                $this->advanceWorkflow($workflow, $nextSteps[0]['id'], [
                    'workflow_data' => ['auto_advanced' => true]
                ]);
            }
        }
    }

    /**
     * Apply business rules
     */
    protected function applyBusinessRules(WorkflowInstance $workflow, string $targetStepId, array $data): void
    {
        $rules = $workflow->template->getRules();
        
        foreach ($rules as $rule) {
            if ($this->evaluateCondition($rule['condition'], $workflow, $data)) {
                $this->executeRuleAction($rule, $workflow, $targetStepId);
            }
        }
    }

    /**
     * Evaluate rule condition
     */
    protected function evaluateCondition(string $condition, WorkflowInstance $workflow, array $data): bool
    {
        // Simple condition evaluation - in production, use a proper expression evaluator
        $document = $workflow->document;
        $workflowData = $workflow->data;
        
        // Replace variables in condition
        $condition = str_replace([
            'document.priority',
            'document.amount',
            'document.type',
            'workflow.days_running'
        ], [
            "'{$document->priority}'",
            $document->amount ?? 0,
            "'{$document->document_type_id}'",
            $workflow->started_at->diffInDays(now())
        ], $condition);

        // Evaluate condition (simplified - use a proper evaluator in production)
        try {
            return eval("return {$condition};");
        } catch (\Exception $e) {
            Log::warning("Failed to evaluate condition: {$condition}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Execute rule action
     */
    protected function executeRuleAction(array $rule, WorkflowInstance $workflow, string $targetStepId): void
    {
        switch ($rule['action']) {
            case 'skip_step':
                // Logic to skip a step
                break;
            case 'require_additional_approval':
                // Logic to add additional approval step
                break;
            case 'escalate':
                // Logic to escalate to higher authority
                break;
            case 'set_priority':
                $workflow->update(['priority' => $rule['value']]);
                break;
        }
    }

    /**
     * Calculate estimated completion date
     */
    protected function calculateEstimatedCompletion(WorkflowTemplate $template): ?\Carbon\Carbon
    {
        $steps = $template->getSteps();
        $totalDays = collect($steps)->sum('time_limit');
        
        return $totalDays > 0 ? now()->addDays($totalDays) : null;
    }

    /**
     * Calculate step due date
     */
    protected function calculateStepDueDate(array $stepConfig): ?\Carbon\Carbon
    {
        $timeLimit = $stepConfig['time_limit'] ?? null;
        return $timeLimit ? now()->addDays($timeLimit) : null;
    }

    /**
     * Determine assignee for step
     */
    protected function determineAssignee(array $stepConfig, Document $document): ?int
    {
        // Auto-assignment logic based on step configuration
        if (isset($stepConfig['assigned_area_id'])) {
            $users = User::where('area_id', $stepConfig['assigned_area_id'])
                         ->where('is_active', true)
                         ->get();
            
            if ($users->isNotEmpty()) {
                // Simple round-robin assignment
                return $users->random()->id;
            }
        }

        // Fallback to document's current area
        if ($document->current_area_id) {
            $users = User::where('area_id', $document->current_area_id)
                         ->where('is_active', true)
                         ->get();
            
            if ($users->isNotEmpty()) {
                return $users->first()->id;
            }
        }

        return null;
    }

    /**
     * Trigger workflow events
     */
    protected function triggerWorkflowEvents(WorkflowInstance $workflow, string $event): void
    {
        // Trigger notifications, webhooks, etc.
        // This is where you'd integrate with your notification system
        
        Log::info("Workflow event triggered", [
            'workflow_id' => $workflow->id,
            'event' => $event
        ]);
    }

    // Additional helper methods for actions
    protected function completeCurrentStep(WorkflowInstance $workflow, array $data): bool
    {
        $nextSteps = $workflow->getNextSteps();
        if (empty($nextSteps)) {
            $this->completeWorkflow($workflow);
            return true;
        }

        return $this->advanceWorkflow($workflow, $nextSteps[0]['id'], $data);
    }

    protected function rejectCurrentStep(WorkflowInstance $workflow, array $data): bool
    {
        $reason = $data['reason'] ?? 'Rechazado por el usuario';
        $this->rejectWorkflow($workflow, $reason);
        return true;
    }

    protected function pauseWorkflow(WorkflowInstance $workflow, array $data): bool
    {
        $reason = $data['reason'] ?? 'Pausado por el usuario';
        $workflow->pause($reason);
        return true;
    }

    protected function approveStep(WorkflowInstance $workflow, array $data): bool
    {
        $data['output_data'] = array_merge($data['output_data'] ?? [], ['approved' => true]);
        return $this->completeCurrentStep($workflow, $data);
    }

    protected function denyStep(WorkflowInstance $workflow, array $data): bool
    {
        $reason = $data['reason'] ?? 'Denegado';
        return $this->rejectCurrentStep($workflow, ['reason' => $reason]);
    }

    protected function validateStep(WorkflowInstance $workflow, array $data): bool
    {
        $data['output_data'] = array_merge($data['output_data'] ?? [], ['validated' => true]);
        return $this->completeCurrentStep($workflow, $data);
    }

    protected function requestCorrection(WorkflowInstance $workflow, array $data): bool
    {
        // Logic to send document back for corrections
        $workflow->document->update(['status' => 'correction_required']);
        return $this->pauseWorkflow($workflow, $data);
    }

    protected function passInspection(WorkflowInstance $workflow, array $data): bool
    {
        $data['output_data'] = array_merge($data['output_data'] ?? [], ['inspection_passed' => true]);
        return $this->completeCurrentStep($workflow, $data);
    }

    protected function failInspection(WorkflowInstance $workflow, array $data): bool
    {
        $data['output_data'] = array_merge($data['output_data'] ?? [], ['inspection_passed' => false]);
        return $this->rejectCurrentStep($workflow, $data);
    }

    // Analytics helper methods
    protected function calculateAverageCompletionTime(Collection $workflows): ?float
    {
        $completed = $workflows->where('status', 'completed')->whereNotNull('completed_at');
        
        if ($completed->isEmpty()) {
            return null;
        }

        $totalHours = $completed->sum(function ($workflow) {
            return $workflow->started_at->diffInHours($workflow->completed_at);
        });

        return round($totalHours / $completed->count(), 2);
    }

    protected function identifyBottleneckSteps(Collection $workflows): array
    {
        // Analyze which steps take the longest on average
        $stepTimes = [];
        
        foreach ($workflows as $workflow) {
            foreach ($workflow->steps as $step) {
                if ($step->status === 'completed' && $step->getDurationHours()) {
                    $stepTimes[$step->step_id][] = $step->getDurationHours();
                }
            }
        }

        $bottlenecks = [];
        foreach ($stepTimes as $stepId => $times) {
            $bottlenecks[$stepId] = [
                'average_hours' => round(array_sum($times) / count($times), 2),
                'max_hours' => max($times),
                'count' => count($times)
            ];
        }

        // Sort by average time descending
        uasort($bottlenecks, fn($a, $b) => $b['average_hours'] <=> $a['average_hours']);

        return $bottlenecks;
    }

    protected function calculateCompletionRate(Collection $workflows): float
    {
        if ($workflows->isEmpty()) {
            return 0;
        }

        $completed = $workflows->where('status', 'completed')->count();
        return round(($completed / $workflows->count()) * 100, 2);
    }

    protected function analyzeStepPerformance(Collection $workflows): array
    {
        $performance = [];
        
        foreach ($workflows as $workflow) {
            foreach ($workflow->steps as $step) {
                $stepId = $step->step_id;
                
                if (!isset($performance[$stepId])) {
                    $performance[$stepId] = [
                        'name' => $step->name,
                        'total_instances' => 0,
                        'completed_instances' => 0,
                        'average_duration' => 0,
                        'overdue_instances' => 0
                    ];
                }

                $performance[$stepId]['total_instances']++;
                
                if ($step->status === 'completed') {
                    $performance[$stepId]['completed_instances']++;
                }
                
                if ($step->isOverdue()) {
                    $performance[$stepId]['overdue_instances']++;
                }
            }
        }

        return $performance;
    }

    protected function getMonthlyTrends(Collection $workflows): array
    {
        $trends = [];
        
        $workflows->groupBy(function ($workflow) {
            return $workflow->started_at->format('Y-m');
        })->each(function ($monthWorkflows, $month) use (&$trends) {
            $trends[$month] = [
                'total' => $monthWorkflows->count(),
                'completed' => $monthWorkflows->where('status', 'completed')->count(),
                'rejected' => $monthWorkflows->where('status', 'rejected')->count(),
                'active' => $monthWorkflows->where('status', 'active')->count()
            ];
        });

        return $trends;
    }
}
