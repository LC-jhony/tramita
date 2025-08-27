<?php

namespace App\Filament\Widgets;

use App\Models\Area;
use App\Models\DocumentMovement;
use Filament\Widgets\ChartWidget;

class AreaPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'Rendimiento por Área';
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $areas = Area::where('status', true)->get();
        $labels = [];
        $pendingData = [];
        $processedData = [];
        $overdueData = [];

        foreach ($areas as $area) {
            $labels[] = $area->name;
            
            // Documentos pendientes
            $pending = DocumentMovement::where('to_area_id', $area->id)
                ->where('status', 'pending')
                ->count();
            $pendingData[] = $pending;
            
            // Documentos procesados este mes
            $processed = DocumentMovement::where('to_area_id', $area->id)
                ->where('status', 'processed')
                ->whereMonth('processed_at', now()->month)
                ->whereYear('processed_at', now()->year)
                ->count();
            $processedData[] = $processed;
            
            // Documentos vencidos
            $overdue = DocumentMovement::where('to_area_id', $area->id)
                ->where('status', 'pending')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count();
            $overdueData[] = $overdue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendientes',
                    'data' => $pendingData,
                    'backgroundColor' => 'rgba(251, 191, 36, 0.8)',
                ],
                [
                    'label' => 'Procesados (Este Mes)',
                    'data' => $processedData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                ],
                [
                    'label' => 'Vencidos',
                    'data' => $overdueData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
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
                ],
            ],
        ];
    }
}
