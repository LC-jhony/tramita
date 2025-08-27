<?php

namespace App\Filament\Widgets;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTemplate;
use App\Models\Document;
use App\Services\WorkflowEngine;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class WorkflowDashboard extends Widget
{
    protected static string $view = 'filament.widgets.workflow-dashboard';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $workflowEngine = app(WorkflowEngine::class);
        
        return [
            'overview' => $this->getOverviewStats(),
            'activeWorkflows' => $this->getActiveWorkflows(),
            'recentActivity' => $this->getRecentActivity(),
            'performanceMetrics' => $this->getPerformanceMetrics(),
            'bottlenecks' => $this->getBottlenecks(),
            'templates' => $this->getTemplateStats(),
            'alerts' => $this->getAlerts()
        ];
    }

    protected function getOverviewStats(): array
    {
        $totalWorkflows = WorkflowInstance::count();
        $activeWorkflows = WorkflowInstance::where('status', 'active')->count();
        $completedToday = WorkflowInstance::where('status', 'completed')
            ->whereDate('completed_at', today())
            ->count();
        $overdueWorkflows = WorkflowInstance::where('status', 'active')
            ->where('estimated_completion', '<', now())
            ->count();

        return [
            'total_workflows' => $totalWorkflows,
            'active_workflows' => $activeWorkflows,
            'completed_today' => $completedToday,
            'overdue_workflows' => $overdueWorkflows,
            'completion_rate' => $totalWorkflows > 0 ? round(($completedToday / $totalWorkflows) * 100, 1) : 0
        ];
    }

    protected function getActiveWorkflows(): array
    {
        return WorkflowInstance::where('status', 'active')
            ->with(['document', 'template', 'currentStepInstance.assignedUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($workflow) {
                $currentStep = $workflow->currentStepInstance();
                return [
                    'id' => $workflow->id,
                    'document_title' => $workflow->document->subject ?? 'Sin título',
                    'template_name' => $workflow->template->name,
                    'current_step' => $currentStep?->name ?? 'N/A',
                    'assigned_to' => $currentStep?->assignedUser?->name ?? 'Sin asignar',
                    'progress' => $workflow->getProgressPercentage(),
                    'due_date' => $currentStep?->due_date,
                    'is_overdue' => $workflow->isOverdue(),
                    'priority' => $workflow->priority,
                    'started_at' => $workflow->started_at
                ];
            })
            ->toArray();
    }

    protected function getRecentActivity(): array
    {
        return DB::table('workflow_steps')
            ->join('workflow_instances', 'workflow_steps.workflow_id', '=', 'workflow_instances.id')
            ->join('documents', 'workflow_instances.document_id', '=', 'documents.id')
            ->join('users', 'workflow_steps.completed_by', '=', 'users.id')
            ->where('workflow_steps.status', 'completed')
            ->whereDate('workflow_steps.completed_at', '>=', now()->subDays(7))
            ->select([
                'workflow_steps.name as step_name',
                'workflow_steps.completed_at',
                'documents.subject as document_title',
                'users.name as completed_by',
                'workflow_steps.type as step_type'
            ])
            ->orderBy('workflow_steps.completed_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    protected function getPerformanceMetrics(): array
    {
        $last30Days = now()->subDays(30);
        
        $completedWorkflows = WorkflowInstance::where('status', 'completed')
            ->where('completed_at', '>=', $last30Days)
            ->get();

        $avgCompletionTime = $completedWorkflows->avg(function ($workflow) {
            return $workflow->started_at->diffInHours($workflow->completed_at);
        });

        $stepPerformance = DB::table('workflow_steps')
            ->where('status', 'completed')
            ->where('completed_at', '>=', $last30Days)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->select([
                'type',
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, started_at, completed_at)) as avg_hours'),
                DB::raw('COUNT(*) as total_steps')
            ])
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->toArray();

        return [
            'avg_completion_time_hours' => round($avgCompletionTime ?? 0, 1),
            'total_completed_last_30_days' => $completedWorkflows->count(),
            'step_performance' => $stepPerformance,
            'efficiency_trend' => $this->getEfficiencyTrend()
        ];
    }

    protected function getBottlenecks(): array
    {
        return DB::table('workflow_steps')
            ->where('status', 'active')
            ->where('due_date', '<', now())
            ->join('workflow_instances', 'workflow_steps.workflow_id', '=', 'workflow_instances.id')
            ->join('documents', 'workflow_instances.document_id', '=', 'documents.id')
            ->join('users', 'workflow_steps.assigned_to', '=', 'users.id')
            ->select([
                'workflow_steps.name as step_name',
                'workflow_steps.type as step_type',
                'documents.subject as document_title',
                'users.name as assigned_to',
                'workflow_steps.due_date',
                DB::raw('DATEDIFF(NOW(), workflow_steps.due_date) as days_overdue')
            ])
            ->orderBy('days_overdue', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getTemplateStats(): array
    {
        return WorkflowTemplate::withCount(['workflows'])
            ->where('is_active', true)
            ->get()
            ->map(function ($template) {
                $activeCount = $template->workflows()->where('status', 'active')->count();
                $completedCount = $template->workflows()->where('status', 'completed')->count();
                $avgCompletionTime = $template->workflows()
                    ->where('status', 'completed')
                    ->whereNotNull('completed_at')
                    ->get()
                    ->avg(function ($workflow) {
                        return $workflow->started_at->diffInDays($workflow->completed_at);
                    });

                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'category' => $template->category,
                    'total_workflows' => $template->workflows_count,
                    'active_workflows' => $activeCount,
                    'completed_workflows' => $completedCount,
                    'avg_completion_days' => round($avgCompletionTime ?? 0, 1),
                    'success_rate' => $template->workflows_count > 0 
                        ? round(($completedCount / $template->workflows_count) * 100, 1) 
                        : 0
                ];
            })
            ->toArray();
    }

    protected function getAlerts(): array
    {
        $alerts = [];

        // Overdue workflows
        $overdueCount = WorkflowInstance::where('status', 'active')
            ->where('estimated_completion', '<', now())
            ->count();

        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Workflows Vencidos',
                'message' => "{$overdueCount} workflows han excedido su fecha estimada de finalización",
                'action_url' => '/admin/workflows?filter[overdue]=1',
                'action_text' => 'Ver workflows vencidos'
            ];
        }

        // High workload areas
        $highWorkloadAreas = DB::table('workflow_steps')
            ->join('users', 'workflow_steps.assigned_to', '=', 'users.id')
            ->join('areas', 'users.area_id', '=', 'areas.id')
            ->where('workflow_steps.status', 'active')
            ->select([
                'areas.name as area_name',
                DB::raw('COUNT(*) as active_steps')
            ])
            ->groupBy('areas.id', 'areas.name')
            ->having('active_steps', '>', 10)
            ->orderBy('active_steps', 'desc')
            ->get();

        foreach ($highWorkloadAreas as $area) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Alta Carga de Trabajo',
                'message' => "El área '{$area->area_name}' tiene {$area->active_steps} pasos activos",
                'action_url' => "/admin/areas/{$area->area_name}",
                'action_text' => 'Ver detalles del área'
            ];
        }

        // Templates with low success rate
        $lowSuccessTemplates = WorkflowTemplate::withCount([
            'workflows',
            'workflows as completed_count' => function ($query) {
                $query->where('status', 'completed');
            }
        ])
        ->get()
        ->filter(function ($template) {
            $successRate = $template->workflows_count > 0 
                ? ($template->completed_count / $template->workflows_count) * 100 
                : 0;
            return $template->workflows_count >= 5 && $successRate < 70;
        });

        foreach ($lowSuccessTemplates as $template) {
            $successRate = round(($template->completed_count / $template->workflows_count) * 100, 1);
            $alerts[] = [
                'type' => 'error',
                'title' => 'Baja Tasa de Éxito',
                'message' => "El template '{$template->name}' tiene una tasa de éxito del {$successRate}%",
                'action_url' => "/admin/workflow-templates/{$template->id}",
                'action_text' => 'Revisar template'
            ];
        }

        return $alerts;
    }

    protected function getEfficiencyTrend(): array
    {
        $last12Months = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $completed = WorkflowInstance::where('status', 'completed')
                ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                ->count();

            $avgTime = WorkflowInstance::where('status', 'completed')
                ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                ->get()
                ->avg(function ($workflow) {
                    return $workflow->started_at->diffInDays($workflow->completed_at);
                });

            return [
                'month' => $date->format('M Y'),
                'completed_workflows' => $completed,
                'avg_completion_days' => round($avgTime ?? 0, 1)
            ];
        });

        return $last12Months->toArray();
    }
}
