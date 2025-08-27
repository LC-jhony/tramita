<?php

namespace App\Enums;

enum WorkflowStageType: string
{
    case RECEPTION = 'reception';
    case REVIEW = 'review';
    case ANALYSIS = 'analysis';
    case APPROVAL = 'approval';
    case SIGNATURE = 'signature';
    case NOTIFICATION = 'notification';
    case ARCHIVE = 'archive';
    case RESPONSE = 'response';
    case VALIDATION = 'validation';
    case PROCESSING = 'processing';

    public function label(): string
    {
        return match($this) {
            self::RECEPTION => 'Recepción',
            self::REVIEW => 'Revisión',
            self::ANALYSIS => 'Análisis',
            self::APPROVAL => 'Aprobación',
            self::SIGNATURE => 'Firma',
            self::NOTIFICATION => 'Notificación',
            self::ARCHIVE => 'Archivo',
            self::RESPONSE => 'Respuesta',
            self::VALIDATION => 'Validación',
            self::PROCESSING => 'Procesamiento',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::RECEPTION => 'Etapa inicial de recepción del documento',
            self::REVIEW => 'Revisión del contenido y documentación',
            self::ANALYSIS => 'Análisis técnico o legal del documento',
            self::APPROVAL => 'Aprobación por autoridad competente',
            self::SIGNATURE => 'Firma del documento o resolución',
            self::NOTIFICATION => 'Notificación a partes interesadas',
            self::ARCHIVE => 'Archivo del documento procesado',
            self::RESPONSE => 'Elaboración de respuesta al solicitante',
            self::VALIDATION => 'Validación de requisitos y documentación',
            self::PROCESSING => 'Procesamiento y gestión del trámite',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::RECEPTION => 'heroicon-o-inbox-arrow-down',
            self::REVIEW => 'heroicon-o-eye',
            self::ANALYSIS => 'heroicon-o-magnifying-glass',
            self::APPROVAL => 'heroicon-o-check-circle',
            self::SIGNATURE => 'heroicon-o-pencil',
            self::NOTIFICATION => 'heroicon-o-bell',
            self::ARCHIVE => 'heroicon-o-archive-box',
            self::RESPONSE => 'heroicon-o-chat-bubble-left-right',
            self::VALIDATION => 'heroicon-o-shield-check',
            self::PROCESSING => 'heroicon-o-cog-6-tooth',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::RECEPTION => 'blue',
            self::REVIEW => 'yellow',
            self::ANALYSIS => 'purple',
            self::APPROVAL => 'green',
            self::SIGNATURE => 'indigo',
            self::NOTIFICATION => 'orange',
            self::ARCHIVE => 'gray',
            self::RESPONSE => 'cyan',
            self::VALIDATION => 'emerald',
            self::PROCESSING => 'amber',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
