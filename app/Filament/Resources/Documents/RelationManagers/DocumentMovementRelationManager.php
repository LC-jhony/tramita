<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Actions\DissociateBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\RelationManagers\RelationManager;

class DocumentMovementRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_area_id')
                    ->label('Desde Área')
                    ->relationship('fromArea', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('to_area_id')
                    ->label('Hacia Área')
                    ->relationship('toArea', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('observations')
                    ->label('Observaciones'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'received' => 'Recibido',
                        'rejected' => 'Rechazado',
                        'processed' => 'Procesado',
                    ])
                    ->required(),
                DateTimePicker::make('received_at')
                    ->label('Fecha de Recepción'),
                DateTimePicker::make('processed_at')
                    ->label('Fecha de Procesamiento'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('fromArea.name')
                    ->label('Desde Área'),
                TextColumn::make('toArea.name')
                    ->label('Hacia Área'),
                TextColumn::make('user.name')
                    ->label('Usuario'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'received' => 'info',
                        'rejected' => 'danger',
                        'processed' => 'success',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'received' => 'Recibido',
                        'rejected' => 'Rechazado',
                        'processed' => 'Procesado',
                    })
                    ->label('Estado'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Fecha de Creación'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
