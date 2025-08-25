<?php

namespace App\Models;

use App\Models\Area;
use App\Models\User;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'representation',
        'full_name',
        'first_name',
        'last_name',
        'dni',
        'ruc',
        'empresa',
        'phone',
        'email',
        'address',
        // datos del Documento
        'number',
        'subject',
        'origen',
        'document_type_id',
        'area_oreigen_id',
        'gestion_id',
        'user_id',
        'folio',
        // 'receip_date',
        'reception_date',
        'file_path',
        'condition',
        'status',
    ];
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(
            DocumentType::class,
            'document_type_id'
        );
    }

    public function areaOrigen(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_oreigen_id'
        );
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(
            Gestion::class,
            'gestion_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
