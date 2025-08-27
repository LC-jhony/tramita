<?php

namespace App\Enums;

enum Priority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::LOW => 'Baja',
            self::NORMAL => 'Normal',
            self::HIGH => 'Alta',
            self::URGENT => 'Urgente',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::LOW => 'Prioridad baja - Sin urgencia específica',
            self::NORMAL => 'Prioridad normal - Procesamiento estándar',
            self::HIGH => 'Prioridad alta - Requiere atención prioritaria',
            self::URGENT => 'Prioridad urgente - Requiere atención inmediata',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW => 'success',
            self::NORMAL => 'primary',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::LOW => 'heroicon-o-arrow-down',
            self::NORMAL => 'heroicon-o-minus',
            self::HIGH => 'heroicon-o-arrow-up',
            self::URGENT => 'heroicon-o-exclamation-triangle',
        };
    }

    public function defaultTimeLimitDays(): int
    {
        return match($this) {
            self::LOW => 15,
            self::NORMAL => 10,
            self::HIGH => 5,
            self::URGENT => 2,
        };
    }

    public function reminderDaysBefore(): int
    {
        return match($this) {
            self::LOW => 3,
            self::NORMAL => 2,
            self::HIGH => 1,
            self::URGENT => 0,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
