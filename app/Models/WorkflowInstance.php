<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'template_id',
        'current_step',
        'status',
        'data',
        'started_at',
        'completed_at',
        'estimated_completion',
        'priority'
    ];

    protected $casts = [
        'data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_completion' => 'datetime'
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'template_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'workflow_id');
    }

    public function currentStepInstance(): ?WorkflowStep
    {
        return $this->steps()->where('step_id', $this->current_step)->first();
    }

    /**
     * Get workflow progress percentage
     */
    public function getProgressPercentage(): float
    {
        $totalSteps = count($this->template->getSteps());
        $completedSteps = $this->steps()->where('status', 'completed')->count();
        
        return $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100, 2) : 0;
    }

    /**
     * Get completed steps
     */
    public function getCompletedSteps(): array
    {
        return $this->steps()
            ->where('status', 'completed')
            ->orderBy('completed_at')
            ->get()
            ->toArray();
    }

    /**
     * Get pending steps
     */
    public function getPendingSteps(): array
    {
        $templateSteps = collect($this->template->getSteps());
        $completedStepIds = $this->steps()
            ->where('status', 'completed')
            ->pluck('step_id')
            ->toArray();

        return $templateSteps
            ->whereNotIn('id', $completedStepIds)
            ->values()
            ->toArray();
    }

    /**
     * Get next possible steps
     */
    public function getNextSteps(): array
    {
        $connections = collect($this->template->getConnections());
        $nextStepIds = $connections
            ->where('from', $this->current_step)
            ->pluck('to')
            ->toArray();

        $templateSteps = collect($this->template->getSteps());
        
        return $templateSteps
            ->whereIn('id', $nextStepIds)
            ->values()
            ->toArray();
    }

    /**
     * Advance to next step
     */
    public function advanceToStep(string $stepId, array $data = []): bool
    {
        $nextSteps = collect($this->getNextSteps());
        $targetStep = $nextSteps->firstWhere('id', $stepId);

        if (!$targetStep) {
            return false;
        }

        // Complete current step
        $currentStep = $this->currentStepInstance();
        if ($currentStep) {
            $currentStep->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'output_data' => $data
            ]);
        }

        // Create new step instance
        WorkflowStep::create([
            'workflow_id' => $this->id,
            'step_id' => $stepId,
            'name' => $targetStep['name'],
            'type' => $targetStep['type'],
            'status' => 'active',
            'assigned_to' => $this->determineAssignee($targetStep),
            'started_at' => now(),
            'due_date' => $this->calculateDueDate($targetStep),
            'input_data' => $data
        ]);

        // Update workflow instance
        $this->update([
            'current_step' => $stepId,
            'data' => array_merge($this->data ?? [], $data)
        ]);

        // Check if workflow is complete
        if ($targetStep['type'] === 'end') {
            $this->complete();
        }

        return true;
    }

    /**
     * Complete workflow
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        // Update document status
        $this->document->update([
            'status' => 'completed'
        ]);
    }

    /**
     * Reject workflow
     */
    public function reject(string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'completed_at' => now(),
            'data' => array_merge($this->data ?? [], ['rejection_reason' => $reason])
        ]);

        // Update current step
        $currentStep = $this->currentStepInstance();
        if ($currentStep) {
            $currentStep->update([
                'status' => 'rejected',
                'completed_at' => now(),
                'output_data' => ['rejection_reason' => $reason]
            ]);
        }

        // Update document status
        $this->document->update([
            'status' => 'rejected'
        ]);
    }

    /**
     * Pause workflow
     */
    public function pause(string $reason = null): void
    {
        $this->update([
            'status' => 'paused',
            'data' => array_merge($this->data ?? [], ['pause_reason' => $reason])
        ]);
    }

    /**
     * Resume workflow
     */
    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Check if workflow is overdue
     */
    public function isOverdue(): bool
    {
        return $this->estimated_completion && 
               $this->estimated_completion->isPast() && 
               $this->status === 'active';
    }

    /**
     * Get workflow timeline
     */
    public function getTimeline(): array
    {
        $timeline = [];
        
        // Add workflow start
        $timeline[] = [
            'type' => 'workflow_started',
            'title' => 'Workflow Iniciado',
            'description' => "Workflow '{$this->template->name}' iniciado",
            'timestamp' => $this->started_at,
            'user' => null,
            'icon' => 'play',
            'color' => 'blue'
        ];

        // Add completed steps
        foreach ($this->getCompletedSteps() as $step) {
            $timeline[] = [
                'type' => 'step_completed',
                'title' => $step['name'],
                'description' => "Paso completado",
                'timestamp' => $step['completed_at'],
                'user' => $step['completed_by'],
                'icon' => 'check',
                'color' => 'green',
                'data' => $step['output_data']
            ];
        }

        // Add current step
        $currentStep = $this->currentStepInstance();
        if ($currentStep && $this->status === 'active') {
            $timeline[] = [
                'type' => 'step_active',
                'title' => $currentStep->name,
                'description' => "Paso en proceso",
                'timestamp' => $currentStep->started_at,
                'user' => $currentStep->assigned_to,
                'icon' => 'clock',
                'color' => 'yellow'
            ];
        }

        // Add completion or rejection
        if ($this->completed_at) {
            $timeline[] = [
                'type' => $this->status === 'completed' ? 'workflow_completed' : 'workflow_rejected',
                'title' => $this->status === 'completed' ? 'Workflow Completado' : 'Workflow Rechazado',
                'description' => $this->status === 'completed' ? 'Proceso finalizado exitosamente' : 'Proceso rechazado',
                'timestamp' => $this->completed_at,
                'user' => null,
                'icon' => $this->status === 'completed' ? 'check-circle' : 'x-circle',
                'color' => $this->status === 'completed' ? 'green' : 'red'
            ];
        }

        return collect($timeline)->sortBy('timestamp')->values()->toArray();
    }

    /**
     * Determine assignee for step
     */
    protected function determineAssignee(array $step): ?int
    {
        // Logic to determine who should be assigned to this step
        // This could be based on step configuration, area, role, etc.
        
        if (isset($step['assigned_area_id'])) {
            // Find users in the specified area
            $users = User::where('area_id', $step['assigned_area_id'])
                         ->where('is_active', true)
                         ->get();
            
            if ($users->isNotEmpty()) {
                // For now, assign to first available user
                // In production, you might want more sophisticated assignment logic
                return $users->first()->id;
            }
        }

        return null;
    }

    /**
     * Calculate due date for step
     */
    protected function calculateDueDate(array $step): ?\Carbon\Carbon
    {
        $timeLimit = $step['time_limit'] ?? null;
        
        if ($timeLimit) {
            return now()->addDays($timeLimit);
        }

        return null;
    }

    /**
     * Get workflow metrics
     */
    public function getMetrics(): array
    {
        $totalSteps = count($this->template->getSteps());
        $completedSteps = $this->steps()->where('status', 'completed')->count();
        $avgStepTime = $this->getAverageStepTime();
        
        return [
            'progress_percentage' => $this->getProgressPercentage(),
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'remaining_steps' => $totalSteps - $completedSteps,
            'average_step_time' => $avgStepTime,
            'estimated_completion' => $this->estimated_completion,
            'is_overdue' => $this->isOverdue(),
            'days_running' => $this->started_at->diffInDays(now()),
            'status' => $this->status
        ];
    }

    /**
     * Get average time per step
     */
    protected function getAverageStepTime(): ?float
    {
        $completedSteps = $this->steps()
            ->where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($completedSteps->isEmpty()) {
            return null;
        }

        $totalHours = $completedSteps->sum(function ($step) {
            return $step->started_at->diffInHours($step->completed_at);
        });

        return round($totalHours / $completedSteps->count(), 2);
    }
}
