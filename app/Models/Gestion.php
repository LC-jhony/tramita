<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class Gestion extends Model
{
    protected $fillable = [
        'start_year',
        'end_year',
        'name',
        'namagement',
        'active',
    ];
    protected $casts = [
        'start_year' => 'integer',
        'end_year'   => 'integer',
        'active'     => 'boolean',
    ];
    protected static function booted(): void
    {
        static::saving(function (Gestion $model) {
            // Forzar consistencia de años y nombre
            if ($model->start_year && !$model->end_year) {
                $model->end_year = (int) $model->start_year + 3;
            } elseif ($model->end_year && !$model->start_year) {
                $model->start_year = (int) $model->end_year - 3;
            }

            if ($model->start_year && $model->end_year) {
                if ((int) $model->end_year !== (int) $model->start_year + 3) {
                    throw ValidationException::withMessages([
                        'end_year' => 'La duración debe ser de 4 años (fin = inicio + 3).',
                    ]);
                }
                $model->name = "Gestión {$model->start_year}-{$model->end_year}";
            }

            // Validar solapamientos
            if ($model->start_year && $model->end_year) {
                $overlaps = static::query()
                    ->when($model->exists, fn($q) => $q->whereKeyNot($model->getKey()))
                    ->where(function ($q) use ($model) {
                        $q->whereBetween('start_year', [$model->start_year, $model->end_year])
                            ->orWhereBetween('end_year', [$model->start_year, $model->end_year])
                            ->orWhere(function ($q2) use ($model) {
                                $q2->where('start_year', '<=', $model->start_year)
                                    ->where('end_year', '>=', $model->end_year);
                            });
                    })
                    ->exists();

                if ($overlaps) {
                    throw ValidationException::withMessages([
                        'start_year' => 'Las gestiones no pueden solaparse con otra existente.',
                        'end_year'   => 'Las gestiones no pueden solaparse con otra existente.',
                    ]);
                }
            }
        });

        static::saved(function (Gestion $model) {
            // Si se activó esta gestión, desactivar las demás
            if ($model->active) {
                static::query()
                    ->whereKeyNot($model->getKey())
                    ->where('active', true)
                    ->update(['active' => false]);
            }
        });
    }
}
