<?php

namespace App\Models;

use App\Models\Area;
use App\Models\User;
use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentHistorie extends Model
{

    protected $fillable = [
        'document_id',
        'action',
        'description',
        'area_id',
        'user_id',
        'changes'
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
