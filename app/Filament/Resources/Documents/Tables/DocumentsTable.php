<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Area;
use App\Models\User;
use App\Models\Gestion;
use App\Models\Document;
use Filament\Tables\Table;
use App\Models\DocumentType;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;
use Filament\Schemas\Components\Section;
use App\Services\DocumentDerivationService;

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
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Borrador',
                        'received' => 'Recibido',
                        'in_process' => 'En Proceso',
                        'derived' => 'Derivado',
                        'returned' => 'Devuelto',
                        'archived' => 'Archivado',
                        'completed' => 'Completado',
                        'rejected' => 'Rechazado',
                    ])
                    ->label('Estado'),
                SelectFilter::make('priority')
                    ->options([
                        1 => 'Normal',
                        2 => 'Urgente',
                        3 => 'Muy Urgente',
                    ])
                    ->label('Prioridad'),
                SelectFilter::make('area_origen_id')
                    ->options(Area::where('status', true)->pluck('name', 'id'))
                    ->label('Área de Origen'),
                SelectFilter::make('current_area_id')
                    ->options(Area::where('status', true)->pluck('name', 'id'))
                    ->label('Área Actual'),
                SelectFilter::make('document_type_id')
                    ->options(DocumentType::where('active', true)->pluck('name', 'id'))
                    ->label('Tipo de Documento'),
                SelectFilter::make('gestion_id')
                    ->options(Gestion::where('active', true)->pluck('name', 'id'))
                    ->label('Gestión'),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Desde'),
                        DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('reception_date')
                    ->form([
                        DatePicker::make('reception_from')
                            ->label('Desde'),
                        DatePicker::make('reception_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['reception_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('reception_date', '>=', $date),
                            )
                            ->when(
                                $data['reception_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('reception_date', '<=', $date),
                            );
                    }),
                Filter::make('due_date')
                    ->form([
                        DatePicker::make('due_from')
                            ->label('Desde'),
                        DatePicker::make('due_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
                Filter::make('has_attachments')
                    ->label('Con archivos adjuntos')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('file_path')),
                Filter::make('urgent_documents')
                    ->label('Documentos urgentes')
                    ->query(fn(Builder $query): Builder => $query->where('priority', '>=', 2)),
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
                        Section::make('Información del Documento')
                            ->schema([
                                Select::make('document_id')
                                    ->label('Documento')
                                    ->options(Document::all()->pluck('number', 'id'))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                TextInput::make('from_area_id')
                                    ->hidden(),
                            ]),

                        Section::make('Derivación')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('to_area_id')
                                            ->label('Derivar a Área')
                                            ->options(Area::where('status', true)->pluck('name', 'id'))
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn(callable $set) => $set('assigned_to', null)),

                                        Select::make('assigned_to')
                                            ->label('Asignar a Usuario Específico')
                                            ->options(function (callable $get) {
                                                $areaId = $get('to_area_id');
                                                if (!$areaId)
                                                    return [];
                                                return User::where('area_id', $areaId)->pluck('name', 'id');
                                            })
                                            ->searchable(),
                                    ]),

                                Grid::make(3)
                                    ->schema([
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
                                            ->label('Fecha Límite')
                                            ->minDate(now())
                                            ->displayFormat('d/m/Y H:i'),
                                    ]),

                                Toggle::make('requires_response')
                                    ->label('Requiere Respuesta')
                                    ->default(false),
                            ]),

                        Section::make('Comentarios e Instrucciones')
                            ->schema([
                                Textarea::make('observations')
                                    ->label('Observaciones')
                                    ->rows(3),

                                Textarea::make('instructions')
                                    ->label('Instrucciones Específicas')
                                    ->rows(3)
                                    ->helperText('Instrucciones detalladas sobre qué hacer con el documento'),
                            ]),

                        Section::make('Archivos Adjuntos')
                            ->schema([
                                Repeater::make('attachments')
                                    ->label('Archivos Adicionales')
                                    ->schema([
                                        AdvancedFileUpload::make('file_path')
                                            ->label('Archivo')
                                            ->required(),

                                        Select::make('attachment_type')
                                            ->label('Tipo de Archivo')
                                            ->options([
                                                'response' => 'Respuesta',
                                                'annex' => 'Anexo',
                                                'support' => 'Documento de Apoyo',
                                                'other' => 'Otro',
                                            ])
                                            ->default('other')
                                            ->required(),

                                        TextInput::make('description')
                                            ->label('Descripción del Archivo')
                                            ->maxLength(255),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('Agregar Archivo')
                                    ->collapsible(),
                            ]),
                    ])
                    ->action(function (Document $record, array $data) {
                        try {
                            $derivationService = app(DocumentDerivationService::class);
                            $movement = $derivationService->deriveDocument($record, $data);

                            $toArea = Area::find($data['to_area_id']);
                            $priorityText = match ($data['priority'] ?? 'normal') {
                                'low' => 'Baja',
                                'normal' => 'Normal',
                                'high' => 'Alta',
                                'urgent' => 'Urgente',
                                default => 'Normal'
                            };

                            Notification::make()
                                ->title('Documento derivado exitosamente')
                                ->body("Derivado a {$toArea->name} con prioridad {$priorityText}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al derivar documento')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
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
