<?php

namespace App\Filament\Resources\Gestions\Schemas;

use App\Models\Gestion;
use Closure;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;

class GestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('start_year')
                    ->label('año Inicio')
                    ->required()
                    ->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        $inicio = (int)$state;
                        if ($inicio > 0) {
                            $fin = $inicio + 3;
                            $set('end_year', $fin);
                            $set('name', "Gestión {$inicio}-{$fin}");
                        }
                    }),
                TextInput::make('end_year')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('namagement')
                    ->label('Alcalde')
                    ->required(),
                Toggle::make('active')
                    ->label('Activo')
                    ->required()
                    ->default(true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        $set('active', $state);
                        // En lugar de emitir un evento usamos una notificación
                        if ($state) {
                            session()->now('filament.notifications', [
                                'title' => 'Estado cambiado',
                                'message' => 'La gestión fue marcada como activa.',
                                'status' => 'success',
                            ]);
                        }
                    }),
            ]);
    }
}
