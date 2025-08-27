<?php

namespace App\Models;

use App\Models\Area;
use App\Models\User;
use App\Models\Document;
use App\Models\DocumentAttachment;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Notifications\DocumentDerivedNotification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentMovement extends Model
{
    protected $fillable = [
        'document_id',
        'from_area_id',
        'to_area_id',
        'user_id',
        'observations',
        'status',
        'received_at',
        'processed_at',
        'priority',
        'movement_type',
        'due_date',
        'instructions',
        'metadata',
        'requires_response',
        'reminder_sent_at',
        'assigned_to'
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'due_date' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'metadata' => 'array',
        'requires_response' => 'boolean'
    ];
    protected static function booted()
    {
        static::created(function ($movement) {
            // Notificar a los usuarios del área destino
            $users = User::where('area_id', $movement->to_area_id)->get();
            Notification::send($users, new DocumentDerivedNotification($movement->document, $movement));
        });
    }
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function fromArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'from_area_id');
    }

    public function toArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'to_area_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    public function getPriorityFormattedAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Baja',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => 'Normal'
        };
    }

    public function getMovementTypeFormattedAttribute(): string
    {
        return match ($this->movement_type) {
            'information' => 'Para Información',
            'action' => 'Para Acción',
            'approval' => 'Para Aprobación',
            'review' => 'Para Revisión',
            'archive' => 'Para Archivo',
            default => 'Para Información'
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'success',
            'normal' => 'primary',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'primary'
        };
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status === 'pending';
    }

    public function isDueSoon(): bool
    {
        if (!$this->due_date || $this->status !== 'pending') {
            return false;
        }

        return $this->due_date->diffInHours(now()) <= 24;
    }
}
