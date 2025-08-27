<?php
namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use App\Models\DocumentMovement;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentDerivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $document;
    public $movement;

    public function __construct(Document $document, DocumentMovement $movement)
    {
        $this->document = $document;
        $this->movement = $movement;
    }
    public function via($notifiable)
    {
        $channels = ['database', 'broadcast'];

        // Agregar email para prioridades altas y urgentes
        if (in_array($this->movement->priority, ['high', 'urgent'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }
    public function toMail($notifiable)
    {
        $priorityText = match ($this->movement->priority) {
            'low' => 'Baja',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => 'Normal'
        };

        $typeText = match ($this->movement->movement_type) {
            'information' => 'Para Información',
            'action' => 'Para Acción',
            'approval' => 'Para Aprobación',
            'review' => 'Para Revisión',
            'archive' => 'Para Archivo',
            default => 'Para Información'
        };

        $subject = $this->movement->priority === 'urgent'
            ? '🚨 URGENTE - Documento Derivado - ' . $this->document->number
            : 'Documento Derivado - ' . $this->document->number;

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting('¡Hola!')
            ->line('Se ha derivado un documento a su área.')
            ->line('**Detalles del Documento:**')
            ->line('• Número: ' . $this->document->number)
            ->line('• Asunto: ' . ($this->document->subject ?? 'Sin asunto'))
            ->line('• Área de origen: ' . $this->movement->fromArea->name)
            ->line('• Prioridad: ' . $priorityText)
            ->line('• Tipo: ' . $typeText);

        if ($this->movement->due_date) {
            $mailMessage->line('• Fecha límite: ' . $this->movement->due_date->format('d/m/Y H:i'));
        }

        if ($this->movement->assigned_to) {
            $mailMessage->line('• Asignado a: ' . $this->movement->assignedTo->name);
        }

        if ($this->movement->observations) {
            $mailMessage->line('**Observaciones:** ' . $this->movement->observations);
        }

        if ($this->movement->instructions) {
            $mailMessage->line('**Instrucciones:** ' . $this->movement->instructions);
        }

        $mailMessage->action('Ver Documento', url('/admin/documents/' . $this->document->id))
            ->line('Gracias por usar nuestro sistema!');

        // Configurar prioridad del email
        if ($this->movement->priority === 'urgent') {
            $mailMessage->priority(1); // Alta prioridad
        } elseif ($this->movement->priority === 'high') {
            $mailMessage->priority(2); // Prioridad media-alta
        }

        return $mailMessage;
    }

    public function toArray($notifiable)
    {
        $priorityText = match ($this->movement->priority) {
            'low' => 'Baja',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => 'Normal'
        };

        $typeText = match ($this->movement->movement_type) {
            'information' => 'Para Información',
            'action' => 'Para Acción',
            'approval' => 'Para Aprobación',
            'review' => 'Para Revisión',
            'archive' => 'Para Archivo',
            default => 'Para Información'
        };

        return [
            'document_id' => $this->document->id,
            'movement_id' => $this->movement->id,
            'type' => 'document_derived',
            'priority' => $this->movement->priority,
            'movement_type' => $this->movement->movement_type,
            'message' => 'Documento ' . $this->document->number . ' derivado a su área',
            'detailed_message' => "Documento {$this->document->number} - {$typeText} (Prioridad: {$priorityText})",
            'from_area' => $this->movement->fromArea->name,
            'due_date' => $this->movement->due_date?->format('d/m/Y H:i'),
            'assigned_to' => $this->movement->assignedTo?->name,
            'requires_response' => $this->movement->requires_response,
            'url' => '/admin/documents/' . $this->document->id,
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}