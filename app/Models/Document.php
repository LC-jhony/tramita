<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'number',
        'subject',
        'origen',
        'representation',
        'full_name',
        'first_name',
        'last_name',
        'dni',
        'phone',
        'email',
        'address',
        'ruc',
        'empresa',
        'document_type_id',
        'area_oreigen_id',
        'gestion_id',
        'user_id',
        'folio',
        'receip_date',
        'reception_date',
        'file_path',
        'condition',
        'status',
    ];

}
