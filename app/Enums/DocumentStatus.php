<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case RECEIVED = 'received';
    case IN_PROCESS = 'in_process';
    case DERIVED = 'derived';
    case RETURNED = 'returned';
    case ARCHIVED = 'archived';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::RECEIVED => 'Recibido',
            self::IN_PROCESS => 'En Proceso',
            self::DERIVED => 'Derivado',
            self::RETURNED => 'Devuelto',
            self::ARCHIVED => 'Archivado',
            self::COMPLETED => 'Completado',
            self::REJECTED => 'Rechazado',
            self::SUSPENDED => 'Suspendido',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::DRAFT => 'Documento en estado de borrador, aún no enviado',
            self::RECEIVED => 'Documento recibido y registrado en el sistema',
            self::IN_PROCESS => 'Documento en proceso de tramitación',
            self::DERIVED => 'Documento derivado a otra área para procesamiento',
            self::RETURNED => 'Documento devuelto para correcciones o información adicional',
            self::ARCHIVED => 'Documento archivado sin procesamiento adicional',
            self::COMPLETED => 'Documento completamente procesado y finalizado',
            self::REJECTED => 'Documento rechazado por no cumplir requisitos',
            self::SUSPENDED => 'Documento suspendido temporalmente',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::RECEIVED => 'blue',
            self::IN_PROCESS => 'yellow',
            self::DERIVED => 'purple',
            self::RETURNED => 'orange',
            self::ARCHIVED => 'slate',
            self::COMPLETED => 'green',
            self::REJECTED => 'red',
            self::SUSPENDED => 'amber',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::DRAFT => 'heroicon-o-document',
            self::RECEIVED => 'heroicon-o-inbox-arrow-down',
            self::IN_PROCESS => 'heroicon-o-cog-6-tooth',
            self::DERIVED => 'heroicon-o-arrow-right-circle',
            self::RETURNED => 'heroicon-o-arrow-left-circle',
            self::ARCHIVED => 'heroicon-o-archive-box',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
            self::SUSPENDED => 'heroicon-o-pause-circle',
        };
    }

    public function isActive(): bool
    {
        return !in_array($this, [
            self::ARCHIVED,
            self::COMPLETED,
            self::REJECTED,
        ]);
    }

    public function canBeModified(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::RECEIVED,
            self::RETURNED,
        ]);
    }

    public function canBeDerived(): bool
    {
        return in_array($this, [
            self::RECEIVED,
            self::IN_PROCESS,
            self::RETURNED,
        ]);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public static function activeStatuses(): array
    {
        return collect(self::cases())
            ->filter(fn($case) => $case->isActive())
            ->pluck('value')
            ->toArray();
    }
}
