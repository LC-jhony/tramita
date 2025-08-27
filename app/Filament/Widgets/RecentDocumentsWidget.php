<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RecentDocumentsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->label('Número'),
                TextColumn::make('subject')
                    ->searchable()
                    ->label('Asunto')
                    ->limit(30),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'received' => 'info',
                        'in_process' => 'primary',
                        'derived' => 'warning',
                        'returned' => 'danger',
                        'archived' => 'success',
                        'completed' => 'success',
                        'rejected' => 'danger',
                    })
                    ->label('Estado'),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn(int $state): string => match ($state) {
                        1 => 'gray',
                        2 => 'warning',
                        3 => 'danger',
                    })
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        1 => 'Normal',
                        2 => 'Urgente',
                        3 => 'Muy Urgente',
                    })
                    ->label('Prioridad'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Creado'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
