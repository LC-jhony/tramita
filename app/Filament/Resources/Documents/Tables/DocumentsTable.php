<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Area;
use App\Models\Document;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->paginated([5, 10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(5)
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
                Action::make('derive')
                    ->label('Derivar')
                    ->icon('iconpark-delivery-o')
                    ->fillForm(fn(Document $record) => [
                        'document_id' => $record->id,
                        'file_path' => $record->file_path,
                    ])
                    ->form([

                        AdvancedFileUpload::make('file_path'),
                        Select::make('document_id')
                            ->label('Documento')
                            ->options(Document::all()->pluck('number', 'id'))
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('from_area_id'),
                        Select::make('user_id')
                            ->label('Derivar a Usuario')
                            ->options(User::all()->pluck('name', 'id'))
                            ->required(),
                        Select::make('to_area_id')
                            ->label('Derivar a Área')
                            ->options(Area::where('status', true)->pluck('name', 'id'))
                            ->required(),
                        Textarea::make('observations')
                            ->label('Observaciones'),
                    ])
                    ->action(function (Document $record, array $data) {
                        // Crear movimiento
                        $movement = $record->movements()->create([
                            'from_area_id' => $record->current_area_id,
                            'to_area_id' => $data['to_area_id'],
                            'user_id' => auth()->id(),
                            'observations' => $data['observations'],
                            'status' => 'pending',
                        ]);

                        // Actualizar documento
                        $record->update([
                            'current_area_id' => $data['to_area_id'],
                            'status' => 'derived',
                        ]);

                        // Crear historial
                        $record->histories()->create([
                            'action' => 'derived',
                            'description' => 'Documento derivado a ' . Area::find($data['to_area_id'])->name,
                            'area_id' => $record->current_area_id,
                            'user_id' => auth()->id(),
                            'changes' => [
                                'from_area' => $record->currentArea->name,
                                'to_area' => Area::find($data['to_area_id'])->name,
                                'observations' => $data['observations']
                            ]
                        ]);
                        Notification::make()
                            ->title('Documento derivado exitosamente')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Document $record) => $record->status !== 'derived' && $record->status !== 'completed' && $record->status !== 'archived'),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
