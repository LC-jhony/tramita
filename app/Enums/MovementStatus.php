<?php

namespace App\Enums;

enum MovementStatus: string
{
    case PENDING = 'pending';
    case RECEIVED = 'received';
    case REJECTED = 'rejected';
    case PROCESSED = 'processed';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::RECEIVED => 'Recibido',
            self::REJECTED => 'Rechazado',
            self::PROCESSED => 'Procesado',
            self::OVERDUE => 'Vencido',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PENDING => 'Movimiento pendiente de recepción',
            self::RECEIVED => 'Movimiento recibido por el área destino',
            self::REJECTED => 'Movimiento rechazado por el área destino',
            self::PROCESSED => 'Movimiento procesado y completado',
            self::OVERDUE => 'Movimiento vencido por exceder el tiempo límite',
            self::CANCELLED => 'Movimiento cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::RECEIVED => 'blue',
            self::REJECTED => 'red',
            self::PROCESSED => 'green',
            self::OVERDUE => 'orange',
            self::CANCELLED => 'gray',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'heroicon-o-clock',
            self::RECEIVED => 'heroicon-o-inbox-arrow-down',
            self::REJECTED => 'heroicon-o-x-circle',
            self::PROCESSED => 'heroicon-o-check-circle',
            self::OVERDUE => 'heroicon-o-exclamation-triangle',
            self::CANCELLED => 'heroicon-o-minus-circle',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::RECEIVED,
        ]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::PROCESSED,
            self::REJECTED,
            self::CANCELLED,
        ]);
    }

    public function requiresAction(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::RECEIVED,
            self::OVERDUE,
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
