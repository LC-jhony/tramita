<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentAttachment extends Model
{
    protected $fillable = [
        'document_id',
        'document_movement_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'attachment_type',
        'description',
        'uploaded_by',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer'
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function documentMovement(): BelongsTo
    {
        return $this->belongsTo(DocumentMovement::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getAttachmentTypeFormattedAttribute(): string
    {
        return match ($this->attachment_type) {
            'original' => 'Documento Original',
            'response' => 'Respuesta',
            'annex' => 'Anexo',
            'support' => 'Documento de Apoyo',
            'other' => 'Otro',
            default => 'Desconocido'
        };
    }
}
