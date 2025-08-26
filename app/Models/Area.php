<?php

namespace App\Models;

use App\Models\Document;
use App\Models\DocumentMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status'

    ];
    protected $casts = [
        'status' => 'boolean'
    ];
    public function documents(): HasMany
    {
        return $this->hasMany(
            Document::class,
            'area_origen_id'
        );
    }

    public function currentDocuments(): HasMany
    {
        return $this->hasMany(
            Document::class,
            'current_area_id'
        );
    }

    public function movementsFrom(): HasMany
    {
        return $this->hasMany(
            DocumentMovement::class,
            'from_area_id'
        );
    }

    public function movementsTo(): HasMany
    {
        return $this->hasMany(
            DocumentMovement::class,
            'to_area_id'
        );
    }
}
