<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasUuids;
    protected $fillable = [
        'name',
        'code',
        'active'
    ];
    protected $casts = [
        'active' => 'boolean'
    ];
}
