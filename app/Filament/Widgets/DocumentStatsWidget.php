<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.document-stats-widget';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Documentos', Document::count())
                ->icon('heroicon-o-document-text'),
            Stat::make('Documentos Pendientes', Document::where('status', 'pending')->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Documentos Urgentes', Document::where('priority', 3)->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Documentos Completados', Document::where('status', 'completed')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
