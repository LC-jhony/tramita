<?php

namespace App\Filament\Widgets;

use App\Models\DocumentMovement;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DocumentMovementsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Derivaciones por Mes';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // Obtener datos de los últimos 12 meses
        $data = [];
        $labels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            
            $count = DocumentMovement::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
                
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Derivaciones',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
