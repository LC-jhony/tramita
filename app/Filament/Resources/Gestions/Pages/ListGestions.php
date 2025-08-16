<?php

namespace App\Filament\Resources\Gestions\Pages;

use App\Filament\Resources\Gestions\GestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGestions extends ListRecords
{
    protected static string $resource = GestionResource::class;

    protected $listeners=[
        'refreshGestionActiva' => '$refresh',
    ];
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-squares-plus'),
        ];
    }
}
