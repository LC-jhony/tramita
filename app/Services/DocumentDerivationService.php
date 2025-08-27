<?php

namespace App\Services;

use App\Models\Area;
use App\Models\User;
use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\DocumentAttachment;
use App\Services\WorkflowService;
use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Enums\Priority;
use App\Enums\DocumentStatus;
use App\Notifications\DocumentDerivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DocumentDerivationService
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function deriveDocument(Document $document, array $data): DocumentMovement
    {
        return DB::transaction(function () use ($document, $data) {
            // Validar que el documento puede ser derivado
            $this->validateDerivation($document, $data);

            // Crear el movimiento
            $movement = $this->createMovement($document, $data);

            // Procesar archivos adjuntos
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                $this->processAttachments($document, $movement, $data['attachments']);
            }

            // Actualizar el documento
            $this->updateDocument($document, $data);

            // Crear historial
            $this->createHistory($document, $movement, $data);

            // Enviar notificaciones
            $this->sendNotifications($movement);

            return $movement;
        });
    }

    /**
     * Automatically advance document to next workflow stage if conditions are met
     */
    public function autoAdvanceWorkflow(Document $document): ?DocumentMovement
    {
        if (!$document->documentType || !$document->documentType->workflow) {
            return null;
        }

        $currentStage = $this->workflowService->getCurrentStage($document);

        if (!$currentStage || !($currentStage['auto_advance'] ?? false)) {
            return null;
        }

        try {
            return $this->workflowService->advanceToNextStage($document, [
                'observations' => 'Avance automático del flujo de trabajo',
                'movement_type' => MovementType::INFORMATION->value,
                'priority' => Priority::NORMAL->value,
            ]);
        } catch (\Exception $e) {
            \Log::warning("Failed to auto-advance workflow for document {$document->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check and process overdue documents
     */
    public function processOverdueDocuments(): array
    {
        $overdueMovements = DocumentMovement::where('status', MovementStatus::PENDING->value)
            ->where('due_date', '<', now())
            ->whereNull('reminder_sent_at')
            ->with(['document', 'toArea', 'user'])
            ->get();

        $processed = [];

        foreach ($overdueMovements as $movement) {
            try {
                // Update movement status
                $movement->update([
                    'status' => MovementStatus::OVERDUE->value,
                    'reminder_sent_at' => now(),
                ]);

                // Send notification
                $this->sendOverdueNotification($movement);

                // Create history entry
                $movement->document->histories()->create([
                    'action' => 'overdue',
                    'description' => "Documento vencido en {$movement->toArea->name}",
                    'area_id' => $movement->to_area_id,
                    'user_id' => $movement->user_id,
                    'changes' => [
                        'movement_id' => $movement->id,
                        'due_date' => $movement->due_date->toISOString(),
                        'days_overdue' => $movement->due_date->diffInDays(now()),
                    ]
                ]);

                $processed[] = $movement->id;
            } catch (\Exception $e) {
                \Log::error("Failed to process overdue movement {$movement->id}: " . $e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Get workflow suggestions for a document
     */
    public function getWorkflowSuggestions(Document $document): array
    {
        if (!$document->documentType || !$document->documentType->workflow) {
            return [];
        }

        $currentStage = $this->workflowService->getCurrentStage($document);
        $nextStages = $this->workflowService->getNextStages($document);

        $suggestions = [];

        foreach ($nextStages as $stage) {
            $area = Area::find($stage['area_id']);

            if (!$area || !$area->status) {
                continue;
            }

            $suggestions[] = [
                'stage' => $stage,
                'area' => $area,
                'recommended_movement_type' => $this->getRecommendedMovementType($stage),
                'recommended_priority' => $this->getRecommendedPriority($stage),
                'estimated_duration' => $stage['time_limit'] ?? null,
                'is_required' => $stage['required'] ?? true,
                'can_auto_advance' => $stage['auto_advance'] ?? false,
            ];
        }

        return $suggestions;
    }

    /**
     * Get recommended movement type for a stage
     */
    protected function getRecommendedMovementType(array $stage): MovementType
    {
        $stageType = $stage['type'] ?? 'processing';

        return match ($stageType) {
            'reception' => MovementType::INFORMATION,
            'review' => MovementType::REVIEW,
            'analysis' => MovementType::REVIEW,
            'approval' => MovementType::APPROVAL,
            'signature' => MovementType::SIGNATURE,
            'notification' => MovementType::INFORMATION,
            'archive' => MovementType::ARCHIVE,
            'response' => MovementType::RESPONSE,
            'validation' => MovementType::VALIDATION,
            'processing' => MovementType::ACTION,
            default => MovementType::INFORMATION,
        };
    }

    /**
     * Get recommended priority for a stage
     */
    protected function getRecommendedPriority(array $stage): Priority
    {
        if (isset($stage['priority'])) {
            return Priority::from($stage['priority']);
        }

        $stageType = $stage['type'] ?? 'processing';

        return match ($stageType) {
            'approval', 'signature' => Priority::HIGH,
            'archive' => Priority::LOW,
            'response' => Priority::HIGH,
            default => Priority::NORMAL,
        };
    }

    /**
     * Send overdue notification
     */
    protected function sendOverdueNotification(DocumentMovement $movement): void
    {
        $users = User::where('area_id', $movement->to_area_id)->get();

        foreach ($users as $user) {
            try {
                $user->notify(new DocumentDerivedNotification($movement->document, $movement, true));
            } catch (\Exception $e) {
                \Log::error("Failed to send overdue notification to user {$user->id}: " . $e->getMessage());
            }
        }
    }

    protected function validateDerivation(Document $document, array $data): void
    {
        // Validar que el documento no esté ya derivado a la misma área
        $existingMovement = $document->movements()
            ->where('to_area_id', $data['to_area_id'])
            ->where('status', 'pending')
            ->first();

        if ($existingMovement) {
            throw new \Exception('El documento ya tiene una derivación pendiente a esta área.');
        }

        // Validar que el área destino esté activa
        $toArea = Area::find($data['to_area_id']);
        if (!$toArea || !$toArea->status) {
            throw new \Exception('El área destino no está activa.');
        }

        // Validar workflow si el tipo de documento lo requiere
        if ($document->documentType && $document->documentType->workflow) {
            $this->validateWorkflow($document, $data);
        }
    }

    protected function validateWorkflow(Document $document, array $data): void
    {
        if (!$document->documentType || !$document->documentType->workflow) {
            return; // No workflow defined, allow any derivation
        }

        $currentStage = $this->workflowService->getCurrentStage($document);
        $targetArea = Area::find($data['to_area_id']);

        if (!$currentStage) {
            throw new \Exception('No se puede determinar la etapa actual del documento');
        }

        // Check if target area matches any valid next stage
        $nextStages = $this->workflowService->getNextStages($document);
        $validTargetStage = $nextStages->first(function ($stage) use ($targetArea) {
            return $stage['area_id'] === $targetArea->id;
        });

        if (!$validTargetStage) {
            // Check if current stage allows derivation to any area (flexible workflow)
            if (!($currentStage['parallel'] ?? false)) {
                throw new \Exception(
                    "La derivación a '{$targetArea->name}' no está permitida desde la etapa actual '{$currentStage['name']}'"
                );
            }
        }

        // Validate movement type compatibility
        $movementType = MovementType::from($data['movement_type'] ?? MovementType::INFORMATION->value);
        if ($validTargetStage && !$this->isMovementTypeCompatible($validTargetStage, $movementType)) {
            throw new \Exception(
                "El tipo de movimiento '{$movementType->label()}' no es compatible con la etapa '{$validTargetStage['name']}'"
            );
        }

        // Check if stage is required and cannot be skipped
        if ($validTargetStage && ($validTargetStage['required'] ?? true)) {
            $this->validateStageRequirements($document, $validTargetStage, $data);
        }
    }

    protected function getCurrentWorkflowStage(Document $document): ?array
    {
        return $this->workflowService->getCurrentStage($document);
    }

    /**
     * Validate if movement type is compatible with stage
     */
    protected function isMovementTypeCompatible(array $stage, MovementType $movementType): bool
    {
        $stageType = $stage['type'] ?? 'processing';

        $compatibleTypes = match ($stageType) {
            'reception' => [MovementType::INFORMATION, MovementType::ACTION],
            'review' => [MovementType::REVIEW, MovementType::INFORMATION],
            'analysis' => [MovementType::REVIEW, MovementType::ACTION],
            'approval' => [MovementType::APPROVAL],
            'signature' => [MovementType::SIGNATURE],
            'notification' => [MovementType::INFORMATION],
            'archive' => [MovementType::ARCHIVE],
            'response' => [MovementType::RESPONSE],
            'validation' => [MovementType::VALIDATION, MovementType::REVIEW],
            'processing' => [MovementType::ACTION, MovementType::INFORMATION],
            default => [MovementType::INFORMATION, MovementType::ACTION],
        };

        return in_array($movementType, $compatibleTypes);
    }

    /**
     * Validate stage-specific requirements
     */
    protected function validateStageRequirements(Document $document, array $stage, array $data): void
    {
        // Check time limits
        if (isset($stage['time_limit']) && $stage['time_limit'] > 0) {
            $lastMovement = $document->movements()
                ->where('status', MovementStatus::PROCESSED->value)
                ->latest()
                ->first();

            if ($lastMovement && $lastMovement->processed_at) {
                $daysSinceLastMovement = $lastMovement->processed_at->diffInDays(now());
                if ($daysSinceLastMovement > $stage['time_limit']) {
                    // Log warning but don't block (could be overdue processing)
                    \Log::warning("Document {$document->id} is overdue for stage {$stage['name']}");
                }
            }
        }

        // Check if stage conditions are met
        if (!empty($stage['conditions'])) {
            $this->validateStageConditions($document, $stage['conditions'], $data);
        }

        // Validate required fields for specific stage types
        $this->validateStageSpecificFields($stage, $data);
    }

    /**
     * Validate stage conditions
     */
    protected function validateStageConditions(Document $document, array $conditions, array $data): void
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field)
                continue;

            $documentValue = data_get($document, $field);
            $dataValue = data_get($data, $field);
            $checkValue = $dataValue ?? $documentValue;

            $isValid = match ($operator) {
                'equals' => $checkValue == $value,
                'not_equals' => $checkValue != $value,
                'greater_than' => $checkValue > $value,
                'less_than' => $checkValue < $value,
                'contains' => str_contains($checkValue, $value),
                'in' => in_array($checkValue, (array) $value),
                'not_in' => !in_array($checkValue, (array) $value),
                default => true,
            };

            if (!$isValid) {
                throw new \Exception(
                    "La condición '{$field} {$operator} {$value}' no se cumple para esta etapa"
                );
            }
        }
    }

    /**
     * Validate stage-specific required fields
     */
    protected function validateStageSpecificFields(array $stage, array $data): void
    {
        $stageType = $stage['type'] ?? 'processing';

        $requiredFields = match ($stageType) {
            'approval' => ['observations', 'instructions'],
            'signature' => ['instructions'],
            'response' => ['observations'],
            'archive' => [],
            default => [],
        };

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception(
                    "El campo '{$field}' es requerido para la etapa de tipo '{$stageType}'"
                );
            }
        }
    }

    protected function createMovement(Document $document, array $data): DocumentMovement
    {
        return $document->movements()->create([
            'from_area_id' => $document->current_area_id,
            'to_area_id' => $data['to_area_id'],
            'user_id' => auth()->id(),
            'assigned_to' => $data['assigned_to'] ?? null,
            'observations' => $data['observations'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'movement_type' => $data['movement_type'] ?? 'information',
            'due_date' => $data['due_date'] ?? null,
            'requires_response' => $data['requires_response'] ?? false,
            'status' => 'pending',
        ]);
    }

    protected function processAttachments(Document $document, DocumentMovement $movement, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (isset($attachment['file_path'])) {
                $document->attachments()->create([
                    'document_movement_id' => $movement->id,
                    'file_name' => basename($attachment['file_path']),
                    'file_path' => $attachment['file_path'],
                    'attachment_type' => $attachment['attachment_type'] ?? 'other',
                    'description' => $attachment['description'] ?? null,
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }

    protected function updateDocument(Document $document, array $data): void
    {
        $document->update([
            'current_area_id' => $data['to_area_id'],
            'status' => 'derived',
        ]);
    }

    protected function createHistory(Document $document, DocumentMovement $movement, array $data): void
    {
        $toArea = Area::find($data['to_area_id']);
        $priorityText = match ($data['priority'] ?? 'normal') {
            'low' => 'Baja',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => 'Normal'
        };

        $typeText = match ($data['movement_type'] ?? 'information') {
            'information' => 'Para Información',
            'action' => 'Para Acción',
            'approval' => 'Para Aprobación',
            'review' => 'Para Revisión',
            'archive' => 'Para Archivo',
            default => 'Para Información'
        };

        $document->histories()->create([
            'action' => 'derived',
            'description' => "Documento derivado a {$toArea->name} - {$typeText} (Prioridad: {$priorityText})",
            'area_id' => $document->current_area_id,
            'user_id' => auth()->id(),
            'changes' => [
                'from_area' => $document->currentArea->name,
                'to_area' => $toArea->name,
                'priority' => $data['priority'] ?? 'normal',
                'movement_type' => $data['movement_type'] ?? 'information',
                'due_date' => $data['due_date'] ?? null,
                'requires_response' => $data['requires_response'] ?? false,
                'observations' => $data['observations'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'attachments_count' => count($data['attachments'] ?? [])
            ]
        ]);
    }

    protected function sendNotifications(DocumentMovement $movement): void
    {
        // Determinar a quién enviar las notificaciones
        $users = collect();

        if ($movement->assigned_to) {
            $user = User::find($movement->assigned_to);
            if ($user) {
                $users->push($user);
            }
        } else {
            // Enviar a todos los usuarios del área destino
            $users = User::where('area_id', $movement->to_area_id)->get();
        }

        // Enviar notificaciones
        foreach ($users as $user) {
            $user->notify(new DocumentDerivedNotification($movement->document, $movement));
        }
    }

    public function receiveDocument(DocumentMovement $movement, array $data = []): void
    {
        DB::transaction(function () use ($movement, $data) {
            $movement->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            $movement->document->histories()->create([
                'action' => 'received',
                'description' => 'Documento recibido en ' . $movement->toArea->name,
                'area_id' => $movement->to_area_id,
                'user_id' => auth()->id(),
                'changes' => [
                    'movement_id' => $movement->id,
                    'received_at' => now()->toISOString(),
                ]
            ]);
        });
    }

    public function processDocument(DocumentMovement $movement, array $data = []): void
    {
        DB::transaction(function () use ($movement, $data) {
            $movement->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            $movement->document->update([
                'status' => 'completed'
            ]);

            $movement->document->histories()->create([
                'action' => 'processed',
                'description' => 'Documento procesado en ' . $movement->toArea->name,
                'area_id' => $movement->to_area_id,
                'user_id' => auth()->id(),
                'changes' => [
                    'movement_id' => $movement->id,
                    'processed_at' => now()->toISOString(),
                    'response' => $data['response'] ?? null,
                ]
            ]);
        });
    }
}
