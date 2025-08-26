<?php

namespace App\Models;

use App\Models\Area;
use App\Models\User;
use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'processed_at'
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

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
}
