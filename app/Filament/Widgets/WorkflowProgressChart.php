<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\WorkflowService;
use App\Enums\DocumentStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WorkflowProgressChart extends ChartWidget
{
    protected static ?string $heading = 'Progreso de Flujos de Trabajo';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $workflowService = app(WorkflowService::class);

        // Get document types with workflows
        $documentTypes = DocumentType::whereNotNull('workflow')
            ->where('active', true)
            ->withCount([
                'documents' => function ($query) {
                    $query->whereIn('status', DocumentStatus::activeStatuses());
                }
            ])
            ->having('documents_count', '>', 0)
            ->get();

        $labels = [];
        $completedData = [];
        $inProgressData = [];

        foreach ($documentTypes as $documentType) {
            $labels[] = $documentType->name;

            $statistics = $workflowService->getWorkflowStatistics($documentType);
            $completedData[] = $statistics['completed_workflows'];
            $inProgressData[] = $statistics['in_progress'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Completados',
                    'data' => $completedData,
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#059669',
                ],
                [
                    'label' => 'En Proceso',
                    'data' => $inProgressData,
                    'backgroundColor' => '#F59E0B',
                    'borderColor' => '#D97706',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
