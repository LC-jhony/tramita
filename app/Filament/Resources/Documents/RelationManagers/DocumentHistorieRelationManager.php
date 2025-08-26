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
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Actions\DissociateBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\RelationManagers\RelationManager;

class DocumentHistorieRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('action')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripción'),
                Select::make('area_id')
                    ->label('Área')
                    ->relationship('area', 'name')
                    ->searchable()
                    ->preload(),
                KeyValue::make('changes')
                    ->label('Cambios'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->columns([
                TextColumn::make('action')
                    ->label('Acción')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50),
                TextColumn::make('area.name')
                    ->label('Área'),
                TextColumn::make('user.name')
                    ->label('Usuario'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Fecha'),
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
