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
                    ->label('Codigo')
                    ->searchable(),
                // TextColumn::make('subject')
                //     ->searchable(),
                TextColumn::make('origen')
                    ->label('Origen')
                    ->color(
                        fn(string $state): string => match ($state) {
                            'Internal' => 'primary',
                            'External' => 'info',

                        }
                    ),
                IconColumn::make('representation')
                    ->label('Representación')
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('full_name')
                    ->label('Nombre Completo')
                    ->formatStateUsing(fn($record) => $record->full_name . ' ' . $record->first_name . ' ' . $record->last_name)
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telefono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ruc')
                    ->label('RUC')
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('empresa')
                    ->label('Empresa')
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('documentType.name')
                    ->label('Documento')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('areaOrigen.name')
                    ->label('Área Origen')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gestion.name')
                    ->label('Gestión')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable(),
                TextColumn::make('reception_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
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
