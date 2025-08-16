<?php

namespace App\Filament\Resources\Gestions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->paginated([5, 10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('start_year')
                    ->label('Año Inicio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('end_year')
                    ->label('Año Fin')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('namagement')
                    ->label('Alcalde')
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Estado')
                    ->searchable()
                    ->boolean(),
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
//                SelectFilter::make('namagement')
//                    ->label('Alcalde')
//                    ->options(Gestion::all()->pluck('namagement', 'id'))
//                    ->searchable()
//                    ->native(false)
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
