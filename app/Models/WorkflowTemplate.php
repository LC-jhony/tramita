<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'is_active',
        'is_system',
        'config',
        'created_by',
        'version'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'config' => 'array'
    ];

    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class, 'workflow_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'template_id');
    }

    /**
     * Get predefined system templates
     */
    public static function getSystemTemplates(): array
    {
        return [
            'simple_approval' => [
                'name' => 'Aprobación Simple',
                'description' => 'Flujo básico: Recepción → Revisión → Aprobación → Archivo',
                'category' => 'basic',
                'steps' => [
                    [
                        'id' => 'reception',
                        'name' => 'Recepción',
                        'type' => 'start',
                        'auto_assign' => true,
                        'time_limit' => 1,
                        'position' => ['x' => 100, 'y' => 100]
                    ],
                    [
                        'id' => 'review',
                        'name' => 'Revisión',
                        'type' => 'task',
                        'requires_approval' => false,
                        'time_limit' => 5,
                        'position' => ['x' => 300, 'y' => 100]
                    ],
                    [
                        'id' => 'approval',
                        'name' => 'Aprobación',
                        'type' => 'approval',
                        'requires_approval' => true,
                        'time_limit' => 3,
                        'position' => ['x' => 500, 'y' => 100]
                    ],
                    [
                        'id' => 'archive',
                        'name' => 'Archivo',
                        'type' => 'end',
                        'auto_complete' => true,
                        'time_limit' => 1,
                        'position' => ['x' => 700, 'y' => 100]
                    ]
                ],
                'connections' => [
                    ['from' => 'reception', 'to' => 'review'],
                    ['from' => 'review', 'to' => 'approval'],
                    ['from' => 'approval', 'to' => 'archive']
                ]
            ],
            'complex_approval' => [
                'name' => 'Aprobación Compleja',
                'description' => 'Flujo con múltiples rutas y aprobaciones paralelas',
                'category' => 'advanced',
                'steps' => [
                    [
                        'id' => 'reception',
                        'name' => 'Recepción',
                        'type' => 'start',
                        'auto_assign' => true,
                        'time_limit' => 1,
                        'position' => ['x' => 100, 'y' => 200]
                    ],
                    [
                        'id' => 'technical_review',
                        'name' => 'Revisión Técnica',
                        'type' => 'task',
                        'parallel' => true,
                        'time_limit' => 7,
                        'position' => ['x' => 300, 'y' => 100]
                    ],
                    [
                        'id' => 'legal_review',
                        'name' => 'Revisión Legal',
                        'type' => 'task',
                        'parallel' => true,
                        'time_limit' => 7,
                        'position' => ['x' => 300, 'y' => 300]
                    ],
                    [
                        'id' => 'manager_approval',
                        'name' => 'Aprobación Gerencial',
                        'type' => 'approval',
                        'requires_approval' => true,
                        'time_limit' => 5,
                        'position' => ['x' => 500, 'y' => 200]
                    ],
                    [
                        'id' => 'notification',
                        'name' => 'Notificación',
                        'type' => 'notification',
                        'auto_complete' => true,
                        'time_limit' => 1,
                        'position' => ['x' => 700, 'y' => 200]
                    ],
                    [
                        'id' => 'archive',
                        'name' => 'Archivo',
                        'type' => 'end',
                        'auto_complete' => true,
                        'time_limit' => 1,
                        'position' => ['x' => 900, 'y' => 200]
                    ]
                ],
                'connections' => [
                    ['from' => 'reception', 'to' => 'technical_review'],
                    ['from' => 'reception', 'to' => 'legal_review'],
                    ['from' => 'technical_review', 'to' => 'manager_approval'],
                    ['from' => 'legal_review', 'to' => 'manager_approval'],
                    ['from' => 'manager_approval', 'to' => 'notification'],
                    ['from' => 'notification', 'to' => 'archive']
                ],
                'rules' => [
                    [
                        'condition' => 'document.priority == "urgent"',
                        'action' => 'skip_step',
                        'target' => 'legal_review'
                    ],
                    [
                        'condition' => 'document.amount > 10000',
                        'action' => 'require_additional_approval',
                        'target' => 'director_approval'
                    ]
                ]
            ],
            'license_workflow' => [
                'name' => 'Licencia de Funcionamiento',
                'description' => 'Flujo específico para licencias comerciales',
                'category' => 'government',
                'steps' => [
                    [
                        'id' => 'reception',
                        'name' => 'Recepción de Solicitud',
                        'type' => 'start',
                        'auto_assign' => true,
                        'time_limit' => 1,
                        'position' => ['x' => 100, 'y' => 150]
                    ],
                    [
                        'id' => 'document_validation',
                        'name' => 'Validación de Documentos',
                        'type' => 'validation',
                        'time_limit' => 3,
                        'position' => ['x' => 300, 'y' => 150]
                    ],
                    [
                        'id' => 'field_inspection',
                        'name' => 'Inspección de Campo',
                        'type' => 'inspection',
                        'time_limit' => 10,
                        'position' => ['x' => 500, 'y' => 150]
                    ],
                    [
                        'id' => 'technical_report',
                        'name' => 'Informe Técnico',
                        'type' => 'report',
                        'time_limit' => 5,
                        'position' => ['x' => 700, 'y' => 150]
                    ],
                    [
                        'id' => 'final_approval',
                        'name' => 'Aprobación Final',
                        'type' => 'approval',
                        'requires_approval' => true,
                        'time_limit' => 3,
                        'position' => ['x' => 900, 'y' => 150]
                    ],
                    [
                        'id' => 'license_issuance',
                        'name' => 'Emisión de Licencia',
                        'type' => 'issuance',
                        'auto_complete' => true,
                        'time_limit' => 2,
                        'position' => ['x' => 1100, 'y' => 150]
                    ]
                ],
                'connections' => [
                    ['from' => 'reception', 'to' => 'document_validation'],
                    ['from' => 'document_validation', 'to' => 'field_inspection'],
                    ['from' => 'field_inspection', 'to' => 'technical_report'],
                    ['from' => 'technical_report', 'to' => 'final_approval'],
                    ['from' => 'final_approval', 'to' => 'license_issuance']
                ],
                'rejection_flow' => [
                    ['from' => 'document_validation', 'to' => 'rejection', 'condition' => 'documents_incomplete'],
                    ['from' => 'field_inspection', 'to' => 'rejection', 'condition' => 'inspection_failed'],
                    ['from' => 'final_approval', 'to' => 'rejection', 'condition' => 'approval_denied']
                ]
            ]
        ];
    }

    /**
     * Create workflow from template
     */
    public function createWorkflow(Document $document): WorkflowInstance
    {
        return WorkflowInstance::create([
            'document_id' => $document->id,
            'template_id' => $this->id,
            'current_step' => $this->getFirstStep(),
            'status' => 'active',
            'data' => [],
            'started_at' => now()
        ]);
    }

    /**
     * Get first step of workflow
     */
    public function getFirstStep(): ?string
    {
        $steps = $this->config['steps'] ?? [];
        $startStep = collect($steps)->firstWhere('type', 'start');
        return $startStep['id'] ?? null;
    }

    /**
     * Get workflow steps
     */
    public function getSteps(): array
    {
        return $this->config['steps'] ?? [];
    }

    /**
     * Get workflow connections
     */
    public function getConnections(): array
    {
        return $this->config['connections'] ?? [];
    }

    /**
     * Get workflow rules
     */
    public function getRules(): array
    {
        return $this->config['rules'] ?? [];
    }

    /**
     * Validate workflow configuration
     */
    public function validateConfig(): array
    {
        $errors = [];
        $config = $this->config;

        if (empty($config['steps'])) {
            $errors[] = 'El workflow debe tener al menos un paso';
            return $errors;
        }

        $steps = collect($config['steps']);
        $stepIds = $steps->pluck('id');

        // Check for duplicate step IDs
        if ($stepIds->count() !== $stepIds->unique()->count()) {
            $errors[] = 'Los pasos no pueden tener IDs duplicados';
        }

        // Check for start step
        if (!$steps->contains('type', 'start')) {
            $errors[] = 'El workflow debe tener un paso de inicio';
        }

        // Check for end step
        if (!$steps->contains('type', 'end')) {
            $errors[] = 'El workflow debe tener un paso de finalización';
        }

        // Validate connections
        $connections = collect($config['connections'] ?? []);
        foreach ($connections as $connection) {
            if (!$stepIds->contains($connection['from'])) {
                $errors[] = "Conexión inválida: paso '{$connection['from']}' no existe";
            }
            if (!$stepIds->contains($connection['to'])) {
                $errors[] = "Conexión inválida: paso '{$connection['to']}' no existe";
            }
        }

        return $errors;
    }
}
