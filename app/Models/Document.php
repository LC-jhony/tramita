<?php

namespace App\Models;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Document extends Model
{
    use HasUuids;
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
    public function documentTypes()
    {
        return $this->hasMany(DocumentType::class);
    }
}
