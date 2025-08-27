<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_id',
        'name',
        'type',
        'status',
        'assigned_to',
        'started_at',
        'completed_at',
        'due_date',
        'completed_by',
        'input_data',
        'output_data',
        'notes'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'datetime',
        'input_data' => 'array',
        'output_data' => 'array'
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Check if step is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               $this->status === 'active';
    }

    /**
     * Check if step is due soon
     */
    public function isDueSoon(int $hours = 24): bool
    {
        return $this->due_date && 
               $this->due_date->diffInHours(now()) <= $hours && 
               $this->status === 'active';
    }

    /**
     * Get step duration in hours
     */
    public function getDurationHours(): ?float
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInHours($this->completed_at);
    }

    /**
     * Get step status with color
     */
    public function getStatusWithColor(): array
    {
        return match($this->status) {
            'active' => ['label' => 'Activo', 'color' => 'blue'],
            'completed' => ['label' => 'Completado', 'color' => 'green'],
            'rejected' => ['label' => 'Rechazado', 'color' => 'red'],
            'paused' => ['label' => 'Pausado', 'color' => 'yellow'],
            'cancelled' => ['label' => 'Cancelado', 'color' => 'gray'],
            default => ['label' => 'Desconocido', 'color' => 'gray']
        };
    }

    /**
     * Get step type icon
     */
    public function getTypeIcon(): string
    {
        return match($this->type) {
            'start' => 'heroicon-o-play',
            'end' => 'heroicon-o-flag',
            'task' => 'heroicon-o-clipboard-document-list',
            'approval' => 'heroicon-o-check-circle',
            'validation' => 'heroicon-o-shield-check',
            'inspection' => 'heroicon-o-magnifying-glass',
            'report' => 'heroicon-o-document-text',
            'notification' => 'heroicon-o-bell',
            'issuance' => 'heroicon-o-document-duplicate',
            default => 'heroicon-o-cog-6-tooth'
        };
    }

    /**
     * Complete step
     */
    public function complete(array $outputData = [], string $notes = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'output_data' => $outputData,
            'notes' => $notes
        ]);

        return true;
    }

    /**
     * Reject step
     */
    public function reject(string $reason, array $outputData = []): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'output_data' => array_merge($outputData, ['rejection_reason' => $reason]),
            'notes' => $reason
        ]);

        return true;
    }

    /**
     * Pause step
     */
    public function pause(string $reason = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'paused',
            'notes' => $reason
        ]);

        return true;
    }

    /**
     * Resume step
     */
    public function resume(): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }

        $this->update(['status' => 'active']);

        return true;
    }

    /**
     * Reassign step to another user
     */
    public function reassign(int $userId): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update(['assigned_to' => $userId]);

        return true;
    }

    /**
     * Get step summary
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'step_id' => $this->step_id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->getStatusWithColor(),
            'assigned_to' => $this->assignedUser?->name,
            'completed_by' => $this->completedByUser?->name,
            'started_at' => $this->started_at?->format('d/m/Y H:i'),
            'completed_at' => $this->completed_at?->format('d/m/Y H:i'),
            'due_date' => $this->due_date?->format('d/m/Y H:i'),
            'duration_hours' => $this->getDurationHours(),
            'is_overdue' => $this->isOverdue(),
            'is_due_soon' => $this->isDueSoon(),
            'icon' => $this->getTypeIcon(),
            'notes' => $this->notes
        ];
    }
}
