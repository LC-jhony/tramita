<?php

namespace App\Models;

use App\Models\Area;
use App\Models\Document;
use App\Enums\WorkflowStageType;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'active',
        'workflow'
    ];

    protected $casts = [
        'active' => 'boolean',
        'workflow' => 'array'
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get workflow stages with enhanced structure
     */
    public function getWorkflowStages(): array
    {
        if (!$this->workflow) {
            return $this->getDefaultWorkflowStages();
        }

        return collect($this->workflow)
            ->map(function ($stage, $index) {
                return [
                    'id' => $stage['id'] ?? $index,
                    'name' => $stage['name'],
                    'type' => $stage['type'] ?? WorkflowStageType::PROCESSING->value,
                    'area_id' => $stage['area_id'] ?? null,
                    'required' => $stage['required'] ?? true,
                    'time_limit' => $stage['time_limit'] ?? null,
                    'order' => $stage['order'] ?? $index,
                    'description' => $stage['description'] ?? null,
                    'conditions' => $stage['conditions'] ?? [],
                    'auto_advance' => $stage['auto_advance'] ?? false,
                    'parallel' => $stage['parallel'] ?? false,
                ];
            })
            ->sortBy('order')
            ->values()
            ->toArray();
    }

    /**
     * Get default workflow stages
     */
    protected function getDefaultWorkflowStages(): array
    {
        return [
            [
                'id' => 0,
                'name' => 'Recepción',
                'type' => WorkflowStageType::RECEPTION->value,
                'area_id' => null,
                'required' => true,
                'time_limit' => 1,
                'order' => 0,
                'description' => 'Recepción inicial del documento',
                'conditions' => [],
                'auto_advance' => false,
                'parallel' => false,
            ],
            [
                'id' => 1,
                'name' => 'Revisión',
                'type' => WorkflowStageType::REVIEW->value,
                'area_id' => null,
                'required' => true,
                'time_limit' => 5,
                'order' => 1,
                'description' => 'Revisión del contenido y documentación',
                'conditions' => [],
                'auto_advance' => false,
                'parallel' => false,
            ],
            [
                'id' => 2,
                'name' => 'Aprobación',
                'type' => WorkflowStageType::APPROVAL->value,
                'area_id' => null,
                'required' => false,
                'time_limit' => 3,
                'order' => 2,
                'description' => 'Aprobación por autoridad competente',
                'conditions' => [],
                'auto_advance' => false,
                'parallel' => false,
            ],
            [
                'id' => 3,
                'name' => 'Archivo',
                'type' => WorkflowStageType::ARCHIVE->value,
                'area_id' => null,
                'required' => true,
                'time_limit' => 1,
                'order' => 3,
                'description' => 'Archivo del documento procesado',
                'conditions' => [],
                'auto_advance' => true,
                'parallel' => false,
            ],
        ];
    }

    /**
     * Get a specific workflow stage by ID or order
     */
    public function getWorkflowStage($identifier): ?array
    {
        $stages = $this->getWorkflowStages();

        if (is_numeric($identifier)) {
            return collect($stages)->firstWhere('id', $identifier)
                ?? collect($stages)->firstWhere('order', $identifier);
        }

        return collect($stages)->firstWhere('name', $identifier);
    }

    /**
     * Get the next workflow stage
     */
    public function getNextWorkflowStage($currentStageId): ?array
    {
        $stages = $this->getWorkflowStages();
        $currentStage = $this->getWorkflowStage($currentStageId);

        if (!$currentStage) {
            return null;
        }

        return collect($stages)
            ->where('order', '>', $currentStage['order'])
            ->sortBy('order')
            ->first();
    }

    /**
     * Get the previous workflow stage
     */
    public function getPreviousWorkflowStage($currentStageId): ?array
    {
        $stages = $this->getWorkflowStages();
        $currentStage = $this->getWorkflowStage($currentStageId);

        if (!$currentStage) {
            return null;
        }

        return collect($stages)
            ->where('order', '<', $currentStage['order'])
            ->sortByDesc('order')
            ->first();
    }

    /**
     * Check if workflow has a specific stage type
     */
    public function hasStageType(WorkflowStageType $type): bool
    {
        return collect($this->getWorkflowStages())
            ->contains('type', $type->value);
    }

    /**
     * Get stages by type
     */
    public function getStagesByType(WorkflowStageType $type): Collection
    {
        return collect($this->getWorkflowStages())
            ->where('type', $type->value);
    }

    /**
     * Get required stages
     */
    public function getRequiredStages(): Collection
    {
        return collect($this->getWorkflowStages())
            ->where('required', true);
    }

    /**
     * Get optional stages
     */
    public function getOptionalStages(): Collection
    {
        return collect($this->getWorkflowStages())
            ->where('required', false);
    }

    /**
     * Validate workflow configuration
     */
    public function validateWorkflow(): array
    {
        $errors = [];
        $stages = $this->getWorkflowStages();

        if (empty($stages)) {
            $errors[] = 'El flujo de trabajo debe tener al menos una etapa';
            return $errors;
        }

        // Check for duplicate orders
        $orders = collect($stages)->pluck('order');
        if ($orders->count() !== $orders->unique()->count()) {
            $errors[] = 'Las etapas no pueden tener el mismo orden';
        }

        // Check for missing required fields
        foreach ($stages as $index => $stage) {
            if (empty($stage['name'])) {
                $errors[] = "La etapa #{$index} debe tener un nombre";
            }

            if (!isset($stage['order']) || !is_numeric($stage['order'])) {
                $errors[] = "La etapa '{$stage['name']}' debe tener un orden válido";
            }
        }

        // Check for valid stage types
        $validTypes = collect(WorkflowStageType::cases())->pluck('value');
        foreach ($stages as $stage) {
            if (!$validTypes->contains($stage['type'])) {
                $errors[] = "La etapa '{$stage['name']}' tiene un tipo inválido";
            }
        }

        return $errors;
    }

    /**
     * Check if workflow is valid
     */
    public function hasValidWorkflow(): bool
    {
        return empty($this->validateWorkflow());
    }

    /**
     * Get workflow summary
     */
    public function getWorkflowSummary(): array
    {
        $stages = $this->getWorkflowStages();

        return [
            'total_stages' => count($stages),
            'required_stages' => $this->getRequiredStages()->count(),
            'optional_stages' => $this->getOptionalStages()->count(),
            'estimated_days' => collect($stages)->sum('time_limit'),
            'has_parallel_stages' => collect($stages)->contains('parallel', true),
            'auto_advance_stages' => collect($stages)->where('auto_advance', true)->count(),
        ];
    }
}
