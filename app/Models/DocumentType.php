<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
}
