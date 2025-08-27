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
                Select::make('assigned_to')
                    ->label('Asignado a')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'low' => 'Baja',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                    ])
                    ->default('normal')
                    ->required(),
                Select::make('movement_type')
                    ->label('Tipo de Derivación')
                    ->options([
                        'information' => 'Para Información',
                        'action' => 'Para Acción',
                        'approval' => 'Para Aprobación',
                        'review' => 'Para Revisión',
                        'archive' => 'Para Archivo',
                    ])
                    ->default('information')
                    ->required(),
                DateTimePicker::make('due_date')
                    ->label('Fecha Límite'),
                Textarea::make('observations')
                    ->label('Observaciones'),
                Textarea::make('instructions')
                    ->label('Instrucciones'),
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
                    ->label('Desde Área')
                    ->sortable(),
                TextColumn::make('toArea.name')
                    ->label('Hacia Área')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Derivado por')
                    ->sortable(),
                TextColumn::make('assignedTo.name')
                    ->label('Asignado a')
                    ->placeholder('Sin asignar'),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'low' => 'success',
                        'normal' => 'primary',
                        'high' => 'warning',
                        'urgent' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'low' => 'Baja',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                    })
                    ->label('Prioridad')
                    ->sortable(),
                TextColumn::make('movement_type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'information' => 'Información',
                        'action' => 'Acción',
                        'approval' => 'Aprobación',
                        'review' => 'Revisión',
                        'archive' => 'Archivo',
                    })
                    ->label('Tipo'),
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
                    ->label('Estado')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->dateTime('d/m/Y H:i')
                    ->label('Fecha Límite')
                    ->color(fn($record) => $record->isOverdue() ? 'danger' : ($record->isDueSoon() ? 'warning' : null))
                    ->placeholder('Sin fecha límite')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Fecha de Creación')
                    ->sortable(),
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
