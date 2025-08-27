<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use App\Models\Area;
use App\Models\WorkflowTemplate;
use App\Enums\WorkflowStageType;
use App\Enums\Priority;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre del Tipo de Documento')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Solicitud de Licencia'),
                                TextInput::make('code')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: SOL-LIC')
                                    ->alphaDash(),
                            ]),
                        Toggle::make('active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Determina si este tipo de documento está disponible para nuevos trámites'),
                    ])
                    ->collapsible(),

                Section::make('Configuración del Flujo de Trabajo')
                    ->description('Seleccione un template predefinido o configure un flujo personalizado.')
                    ->schema([
                        Select::make('workflow_template_id')
                            ->label('Template de Workflow')
                            ->options(function () {
                                $templates = WorkflowTemplate::getSystemTemplates();
                                $options = ['custom' => 'Configuración Personalizada'];

                                foreach ($templates as $key => $template) {
                                    $options[$key] = $template['name'] . ' - ' . $template['description'];
                                }

                                return $options;
                            })
                            ->default('custom')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state !== 'custom') {
                                    $templates = WorkflowTemplate::getSystemTemplates();
                                    if (isset($templates[$state])) {
                                        $template = $templates[$state];
                                        $set('workflow', $template['steps'] ?? []);
                                    }
                                }
                            })
                            ->helperText('Seleccione un template predefinido o "Configuración Personalizada" para crear su propio flujo')
                            ->columnSpanFull(),

                        Repeater::make('workflow')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('id')
                                            ->label('ID')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('ej: reception')
                                            ->helperText('Identificador único del paso')
                                            ->columnSpan(1),

                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('Ej: Recepción de Documentos')
                                            ->columnSpan(2),

                                        Select::make('type')
                                            ->label('Tipo')
                                            ->options([
                                                'start' => 'Inicio',
                                                'task' => 'Tarea',
                                                'approval' => 'Aprobación',
                                                'validation' => 'Validación',
                                                'inspection' => 'Inspección',
                                                'report' => 'Informe',
                                                'notification' => 'Notificación',
                                                'issuance' => 'Emisión',
                                                'end' => 'Fin'
                                            ])
                                            ->default('task')
                                            ->required()
                                            ->columnSpan(1),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        Select::make('assigned_area_id')
                                            ->label('Área Asignada')
                                            ->options(
                                                Area::where('status', true)
                                                    ->pluck('name', 'id')
                                            )
                                            ->searchable()
                                            ->nullable(),

                                        TextInput::make('time_limit')
                                            ->label('Límite (días)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(365)
                                            ->nullable()
                                            ->placeholder('5'),

                                        Select::make('priority')
                                            ->label('Prioridad')
                                            ->options(Priority::options())
                                            ->default(Priority::NORMAL->value),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        Toggle::make('required')
                                            ->label('Requerido')
                                            ->default(true),

                                        Toggle::make('auto_assign')
                                            ->label('Auto-asignar')
                                            ->default(false),

                                        Toggle::make('auto_complete')
                                            ->label('Auto-completar')
                                            ->default(false),

                                        Toggle::make('parallel')
                                            ->label('Paralelo')
                                            ->default(false),
                                    ]),

                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->maxLength(500)
                                    ->rows(2)
                                    ->placeholder('Descripción del paso y acciones requeridas')
                                    ->columnSpanFull(),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('position.x')
                                            ->label('Posición X')
                                            ->numeric()
                                            ->default(100)
                                            ->helperText('Posición horizontal en el canvas'),

                                        TextInput::make('position.y')
                                            ->label('Posición Y')
                                            ->numeric()
                                            ->default(100)
                                            ->helperText('Posición vertical en el canvas'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->itemLabel(fn(array $state): ?string => ($state['name'] ?? 'Nuevo Paso') . ' (' . ($state['type'] ?? 'task') . ')')
                            ->addActionLabel('Agregar Paso')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn($action) => $action->requiresConfirmation()
                            )
                            ->label('Pasos del Workflow')
                            ->columnSpanFull()
                            ->minItems(0)
                            ->maxItems(50),

                        Repeater::make('workflow_connections')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('from')
                                            ->label('Desde (ID del paso)')
                                            ->required()
                                            ->placeholder('reception'),

                                        TextInput::make('to')
                                            ->label('Hacia (ID del paso)')
                                            ->required()
                                            ->placeholder('review'),

                                        TextInput::make('condition')
                                            ->label('Condición')
                                            ->placeholder('document.priority == "high"')
                                            ->helperText('Condición opcional para la transición'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->itemLabel(fn(array $state): ?string => ($state['from'] ?? '') . ' → ' . ($state['to'] ?? ''))
                            ->addActionLabel('Agregar Conexión')
                            ->label('Conexiones entre Pasos')
                            ->columnSpanFull()
                            ->collapsible(),

                        Repeater::make('workflow_rules')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('condition')
                                            ->label('Condición')
                                            ->required()
                                            ->placeholder('document.amount > 10000')
                                            ->helperText('Condición que activa la regla'),

                                        Select::make('action')
                                            ->label('Acción')
                                            ->options([
                                                'skip_step' => 'Saltar Paso',
                                                'require_additional_approval' => 'Requerir Aprobación Adicional',
                                                'escalate' => 'Escalar',
                                                'set_priority' => 'Cambiar Prioridad',
                                                'notify' => 'Notificar',
                                                'assign_to' => 'Asignar a Usuario Específico'
                                            ])
                                            ->required(),

                                        TextInput::make('target')
                                            ->label('Objetivo')
                                            ->placeholder('step_id o user_id')
                                            ->helperText('ID del paso o usuario objetivo'),
                                    ]),

                                TextInput::make('value')
                                    ->label('Valor')
                                    ->placeholder('high, user_id, etc.')
                                    ->helperText('Valor para la acción (si aplica)')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->itemLabel(fn(array $state): ?string => 'Regla: ' . ($state['condition'] ?? 'Nueva regla'))
                            ->addActionLabel('Agregar Regla de Negocio')
                            ->label('Reglas de Negocio')
                            ->columnSpanFull()
                            ->collapsible(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }
}
