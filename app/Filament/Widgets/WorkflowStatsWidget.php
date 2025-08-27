<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentMovement;
use App\Services\WorkflowService;
use App\Enums\DocumentStatus;
use App\Enums\MovementStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class WorkflowStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $workflowService = app(WorkflowService::class);
        
        // Get basic document statistics
        $totalDocuments = Document::whereIn('status', DocumentStatus::activeStatuses())->count();
        $completedDocuments = Document::where('status', DocumentStatus::COMPLETED->value)->count();
        $inProgressDocuments = Document::where('status', DocumentStatus::IN_PROCESS->value)->count();
        
        // Get movement statistics
        $pendingMovements = DocumentMovement::where('status', MovementStatus::PENDING->value)->count();
        $overdueMovements = DocumentMovement::where('status', MovementStatus::OVERDUE->value)
            ->orWhere(function($query) {
                $query->where('status', MovementStatus::PENDING->value)
                      ->where('due_date', '<', now());
            })
            ->count();
        
        // Calculate completion rate
        $completionRate = $totalDocuments > 0 ? round(($completedDocuments / $totalDocuments) * 100, 1) : 0;
        
        // Get average processing time
        $avgProcessingTime = $this->getAverageProcessingTime();
        
        return [
            Stat::make('Documentos Activos', $totalDocuments)
                ->description('Total de documentos en el sistema')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('En Proceso', $inProgressDocuments)
                ->description('Documentos siendo procesados')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('warning'),

            Stat::make('Completados', $completedDocuments)
                ->description("Tasa de finalización: {$completionRate}%")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Movimientos Pendientes', $pendingMovements)
                ->description('Derivaciones por procesar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Documentos Vencidos', $overdueMovements)
                ->description('Requieren atención inmediata')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueMovements > 0 ? 'danger' : 'success'),

            Stat::make('Tiempo Promedio', $avgProcessingTime)
                ->description('Días promedio de procesamiento')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('gray'),
        ];
    }

    protected function getAverageProcessingTime(): string
    {
        $completedDocuments = Document::where('status', DocumentStatus::COMPLETED->value)
            ->whereNotNull('reception_date')
            ->whereNotNull('updated_at')
            ->select(DB::raw('AVG(DATEDIFF(updated_at, reception_date)) as avg_days'))
            ->first();

        $avgDays = $completedDocuments->avg_days ?? 0;
        
        return $avgDays > 0 ? round($avgDays, 1) . ' días' : 'N/A';
    }
}
