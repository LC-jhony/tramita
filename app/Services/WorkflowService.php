<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentMovement;
use App\Models\Area;
use App\Enums\WorkflowStageType;
use App\Enums\MovementType;
use App\Enums\Priority;
use App\Enums\DocumentStatus;
use App\Enums\MovementStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    /**
     * Get the current workflow stage for a document
     */
    public function getCurrentStage(Document $document): ?array
    {
        if (!$document->documentType || !$document->documentType->workflow) {
            return null;
        }

        $lastMovement = $document->movements()
            ->where('status', MovementStatus::PROCESSED->value)
            ->latest()
            ->first();

        $stages = $document->documentType->getWorkflowStages();
        
        if (!$lastMovement) {
            // Return first stage if no movements
            return collect($stages)->sortBy('order')->first();
        }

        // Find current stage based on last movement
        $currentStageIndex = $this->findStageIndexByMovement($lastMovement, $stages);
        
        if ($currentStageIndex !== null && isset($stages[$currentStageIndex + 1])) {
            return $stages[$currentStageIndex + 1];
        }

        // If no next stage, document is at final stage
        return collect($stages)->sortByDesc('order')->first();
    }

    /**
     * Get the next possible stages for a document
     */
    public function getNextStages(Document $document): Collection
    {
        $currentStage = $this->getCurrentStage($document);
        
        if (!$currentStage) {
            return collect();
        }

        $stages = $document->documentType->getWorkflowStages();
        
        return collect($stages)
            ->where('order', '>', $currentStage['order'])
            ->sortBy('order');
    }

    /**
     * Check if a document can advance to a specific stage
     */
    public function canAdvanceToStage(Document $document, array $targetStage): bool
    {
        $currentStage = $this->getCurrentStage($document);
        
        if (!$currentStage) {
            return false;
        }

        // Check if target stage is the immediate next stage
        $nextStages = $this->getNextStages($document);
        $immediateNext = $nextStages->first();
        
        if ($immediateNext && $immediateNext['id'] === $targetStage['id']) {
            return true;
        }

        // Check if current stage allows parallel processing
        if ($currentStage['parallel'] ?? false) {
            return $nextStages->contains('id', $targetStage['id']);
        }

        // Check if target stage is optional and can be skipped
        if (!($targetStage['required'] ?? true)) {
            return $nextStages->contains('id', $targetStage['id']);
        }

        return false;
    }

    /**
     * Advance document to next stage
     */
    public function advanceToNextStage(Document $document, ?array $data = []): ?DocumentMovement
    {
        $currentStage = $this->getCurrentStage($document);
        $nextStage = $this->getNextStages($document)->first();
        
        if (!$nextStage) {
            return null;
        }

        return $this->advanceToStage($document, $nextStage, $data);
    }

    /**
     * Advance document to specific stage
     */
    public function advanceToStage(Document $document, array $targetStage, array $data = []): ?DocumentMovement
    {
        if (!$this->canAdvanceToStage($document, $targetStage)) {
            throw new \Exception("No se puede avanzar a la etapa '{$targetStage['name']}'");
        }

        return DB::transaction(function () use ($document, $targetStage, $data) {
            // Determine target area
            $targetAreaId = $targetStage['area_id'] ?? $data['to_area_id'] ?? null;
            
            if (!$targetAreaId) {
                throw new \Exception("La etapa '{$targetStage['name']}' requiere un área responsable");
            }

            // Create movement
            $movement = $this->createStageMovement($document, $targetStage, $targetAreaId, $data);
            
            // Update document status
            $this->updateDocumentStatus($document, $targetStage);
            
            // Create history entry
            $this->createStageHistory($document, $targetStage, $movement);
            
            return $movement;
        });
    }

    /**
     * Get workflow progress for a document
     */
    public function getWorkflowProgress(Document $document): array
    {
        if (!$document->documentType || !$document->documentType->workflow) {
            return [
                'current_stage' => null,
                'completed_stages' => [],
                'remaining_stages' => [],
                'progress_percentage' => 0,
                'estimated_completion' => null,
            ];
        }

        $stages = $document->documentType->getWorkflowStages();
        $currentStage = $this->getCurrentStage($document);
        $completedStages = $this->getCompletedStages($document);
        
        $totalStages = count($stages);
        $completedCount = count($completedStages);
        $progressPercentage = $totalStages > 0 ? ($completedCount / $totalStages) * 100 : 0;
        
        return [
            'current_stage' => $currentStage,
            'completed_stages' => $completedStages,
            'remaining_stages' => $this->getRemainingStages($document),
            'progress_percentage' => round($progressPercentage, 2),
            'estimated_completion' => $this->estimateCompletionDate($document),
            'total_stages' => $totalStages,
            'completed_count' => $completedCount,
        ];
    }

    /**
     * Get completed stages for a document
     */
    public function getCompletedStages(Document $document): array
    {
        $movements = $document->movements()
            ->where('status', MovementStatus::PROCESSED->value)
            ->orderBy('created_at')
            ->get();

        $stages = $document->documentType->getWorkflowStages();
        $completed = [];

        foreach ($movements as $movement) {
            $stageIndex = $this->findStageIndexByMovement($movement, $stages);
            if ($stageIndex !== null && isset($stages[$stageIndex])) {
                $completed[] = array_merge($stages[$stageIndex], [
                    'completed_at' => $movement->processed_at,
                    'processed_by' => $movement->user_id,
                    'area' => $movement->toArea->name ?? null,
                ]);
            }
        }

        return $completed;
    }

    /**
     * Get remaining stages for a document
     */
    public function getRemainingStages(Document $document): array
    {
        $currentStage = $this->getCurrentStage($document);
        
        if (!$currentStage) {
            return [];
        }

        $stages = $document->documentType->getWorkflowStages();
        
        return collect($stages)
            ->where('order', '>=', $currentStage['order'])
            ->sortBy('order')
            ->values()
            ->toArray();
    }

    /**
     * Estimate completion date for a document
     */
    public function estimateCompletionDate(Document $document): ?string
    {
        $remainingStages = $this->getRemainingStages($document);
        
        if (empty($remainingStages)) {
            return null;
        }

        $totalDays = collect($remainingStages)->sum('time_limit');
        
        if ($totalDays <= 0) {
            return null;
        }

        return now()->addDays($totalDays)->format('Y-m-d');
    }

    /**
     * Check if document workflow is complete
     */
    public function isWorkflowComplete(Document $document): bool
    {
        $stages = $document->documentType->getWorkflowStages();
        $completedStages = $this->getCompletedStages($document);
        
        $requiredStages = collect($stages)->where('required', true);
        $completedRequiredStages = collect($completedStages)->where('required', true);
        
        return $requiredStages->count() === $completedRequiredStages->count();
    }

    /**
     * Get workflow statistics for a document type
     */
    public function getWorkflowStatistics(DocumentType $documentType): array
    {
        $documents = $documentType->documents()
            ->whereIn('status', DocumentStatus::activeStatuses())
            ->get();

        $statistics = [
            'total_documents' => $documents->count(),
            'completed_workflows' => 0,
            'in_progress' => 0,
            'overdue' => 0,
            'average_completion_time' => 0,
            'stage_statistics' => [],
        ];

        foreach ($documents as $document) {
            if ($this->isWorkflowComplete($document)) {
                $statistics['completed_workflows']++;
            } else {
                $statistics['in_progress']++;
            }

            // Check for overdue documents
            $activeMovement = $document->activeMovement();
            if ($activeMovement && $activeMovement->isOverdue()) {
                $statistics['overdue']++;
            }
        }

        return $statistics;
    }

    /**
     * Helper method to find stage index by movement
     */
    protected function findStageIndexByMovement(DocumentMovement $movement, array $stages): ?int
    {
        // This is a simplified implementation
        // In a real scenario, you might store stage_id in the movement
        foreach ($stages as $index => $stage) {
            if ($stage['area_id'] === $movement->to_area_id) {
                return $index;
            }
        }
        
        return null;
    }

    /**
     * Create movement for stage advancement
     */
    protected function createStageMovement(Document $document, array $stage, int $targetAreaId, array $data): DocumentMovement
    {
        $movementData = array_merge([
            'document_id' => $document->id,
            'from_area_id' => $document->current_area_id ?? $document->area_oreigen_id,
            'to_area_id' => $targetAreaId,
            'user_id' => auth()->id(),
            'status' => MovementStatus::PENDING->value,
            'movement_type' => $this->getStageMovementType($stage)->value,
            'priority' => $this->getStagePriority($stage)->value,
            'observations' => "Avance automático a etapa: {$stage['name']}",
            'due_date' => $stage['time_limit'] ? now()->addDays($stage['time_limit']) : null,
            'requires_response' => $stage['required'] ?? true,
        ], $data);

        return DocumentMovement::create($movementData);
    }

    /**
     * Update document status based on stage
     */
    protected function updateDocumentStatus(Document $document, array $stage): void
    {
        $stageType = WorkflowStageType::from($stage['type']);
        
        $status = match($stageType) {
            WorkflowStageType::RECEPTION => DocumentStatus::RECEIVED,
            WorkflowStageType::ARCHIVE => DocumentStatus::ARCHIVED,
            default => DocumentStatus::IN_PROCESS,
        };

        $document->update(['status' => $status->value]);
    }

    /**
     * Create history entry for stage advancement
     */
    protected function createStageHistory(Document $document, array $stage, DocumentMovement $movement): void
    {
        $document->histories()->create([
            'action' => 'stage_advance',
            'description' => "Documento avanzado a etapa: {$stage['name']}",
            'area_id' => $movement->to_area_id,
            'user_id' => auth()->id(),
            'changes' => [
                'stage_name' => $stage['name'],
                'stage_type' => $stage['type'],
                'movement_id' => $movement->id,
                'auto_advance' => $stage['auto_advance'] ?? false,
            ]
        ]);
    }

    /**
     * Get movement type for stage
     */
    protected function getStageMovementType(array $stage): MovementType
    {
        $stageType = WorkflowStageType::from($stage['type']);
        
        return match($stageType) {
            WorkflowStageType::RECEPTION => MovementType::INFORMATION,
            WorkflowStageType::REVIEW => MovementType::REVIEW,
            WorkflowStageType::ANALYSIS => MovementType::REVIEW,
            WorkflowStageType::APPROVAL => MovementType::APPROVAL,
            WorkflowStageType::SIGNATURE => MovementType::SIGNATURE,
            WorkflowStageType::NOTIFICATION => MovementType::INFORMATION,
            WorkflowStageType::ARCHIVE => MovementType::ARCHIVE,
            WorkflowStageType::RESPONSE => MovementType::RESPONSE,
            WorkflowStageType::VALIDATION => MovementType::VALIDATION,
            WorkflowStageType::PROCESSING => MovementType::ACTION,
        };
    }

    /**
     * Get priority for stage
     */
    protected function getStagePriority(array $stage): Priority
    {
        if (isset($stage['priority'])) {
            return Priority::from($stage['priority']);
        }

        $stageType = WorkflowStageType::from($stage['type']);
        
        return match($stageType) {
            WorkflowStageType::URGENT => Priority::URGENT,
            WorkflowStageType::APPROVAL, WorkflowStageType::SIGNATURE => Priority::HIGH,
            WorkflowStageType::ARCHIVE => Priority::LOW,
            default => Priority::NORMAL,
        };
    }
}
