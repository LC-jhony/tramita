<?php

namespace App\Models;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'active'
    ];
    protected $casts = [
        'active' => 'boolean'
    ];
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
