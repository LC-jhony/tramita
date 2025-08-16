<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("hola")
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Secretaria'),
                        TextInput::make('code')
                            ->label('Codigo')
                            ->placeholder('SCT'),
                        Toggle::make('status')
                            ->label('Estado')
                            ->inline(false)
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true)
                    ])->columns(3)

            ]);
    }
}
