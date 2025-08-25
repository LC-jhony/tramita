<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                // TextColumn::make('id')
                //     ->label('ID'),
                TextColumn::make('number')
                    ->searchable(),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('origen'),
                IconColumn::make('representation')
                    ->boolean(),
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('first_name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable(),
                TextColumn::make('dni')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('ruc')
                    ->searchable(),
                TextColumn::make('empresa')
                    ->searchable(),
                TextColumn::make('documentType.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('areaOrigen.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gestion.name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('folio')
                    ->searchable(),
                TextColumn::make('reception_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('file_path')
                    ->searchable(),
                TextColumn::make('condition')
                    ->searchable(),
                TextColumn::make('status'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
