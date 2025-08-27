<?php

namespace App\Filament\Widgets;

use App\Models\DocumentMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentMovementsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalMovements = DocumentMovement::count();
        $pendingMovements = DocumentMovement::where('status', 'pending')->count();
        $overdueMovements = DocumentMovement::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();
        $urgentMovements = DocumentMovement::where('status', 'pending')
            ->where('priority', 'urgent')
            ->count();
        $dueSoonMovements = DocumentMovement::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addHours(24))
            ->count();

        return [
            Stat::make('Total Derivaciones', $totalMovements)
                ->description('Todas las derivaciones')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Pendientes', $pendingMovements)
                ->description('Derivaciones pendientes')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Vencidas', $overdueMovements)
                ->description('Derivaciones vencidas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Urgentes', $urgentMovements)
                ->description('Prioridad urgente')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),

            Stat::make('Vencen Pronto', $dueSoonMovements)
                ->description('Vencen en 24 horas')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color('warning'),
        ];
    }
}
