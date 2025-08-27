<?php

namespace App\Enums;

enum MovementType: string
{
    case INFORMATION = 'information';
    case ACTION = 'action';
    case APPROVAL = 'approval';
    case REVIEW = 'review';
    case ARCHIVE = 'archive';
    case RESPONSE = 'response';
    case SIGNATURE = 'signature';
    case VALIDATION = 'validation';

    public function label(): string
    {
        return match($this) {
            self::INFORMATION => 'Para Información',
            self::ACTION => 'Para Acción',
            self::APPROVAL => 'Para Aprobación',
            self::REVIEW => 'Para Revisión',
            self::ARCHIVE => 'Para Archivo',
            self::RESPONSE => 'Para Respuesta',
            self::SIGNATURE => 'Para Firma',
            self::VALIDATION => 'Para Validación',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::INFORMATION => 'Derivación únicamente para conocimiento e información',
            self::ACTION => 'Derivación que requiere una acción específica',
            self::APPROVAL => 'Derivación que requiere aprobación o autorización',
            self::REVIEW => 'Derivación para revisión y análisis del contenido',
            self::ARCHIVE => 'Derivación para archivo del documento',
            self::RESPONSE => 'Derivación para elaborar respuesta al solicitante',
            self::SIGNATURE => 'Derivación para firma de documento o resolución',
            self::VALIDATION => 'Derivación para validar requisitos y documentación',
        };
    }

    public function requiresResponse(): bool
    {
        return match($this) {
            self::INFORMATION, self::ARCHIVE => false,
            self::ACTION, self::APPROVAL, self::REVIEW, self::RESPONSE, self::SIGNATURE, self::VALIDATION => true,
        };
    }

    public function priority(): Priority
    {
        return match($this) {
            self::INFORMATION, self::ARCHIVE => Priority::NORMAL,
            self::REVIEW, self::VALIDATION => Priority::NORMAL,
            self::ACTION, self::RESPONSE => Priority::HIGH,
            self::APPROVAL, self::SIGNATURE => Priority::HIGH,
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::INFORMATION => 'heroicon-o-information-circle',
            self::ACTION => 'heroicon-o-bolt',
            self::APPROVAL => 'heroicon-o-check-circle',
            self::REVIEW => 'heroicon-o-eye',
            self::ARCHIVE => 'heroicon-o-archive-box',
            self::RESPONSE => 'heroicon-o-chat-bubble-left-right',
            self::SIGNATURE => 'heroicon-o-pencil',
            self::VALIDATION => 'heroicon-o-shield-check',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::INFORMATION => 'blue',
            self::ACTION => 'orange',
            self::APPROVAL => 'green',
            self::REVIEW => 'yellow',
            self::ARCHIVE => 'gray',
            self::RESPONSE => 'cyan',
            self::SIGNATURE => 'indigo',
            self::VALIDATION => 'emerald',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
