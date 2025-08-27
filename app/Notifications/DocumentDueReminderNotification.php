<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentDueReminderNotification extends Notification implements ShouldQueue
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
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail($notifiable)
    {
        $hoursRemaining = $this->movement->due_date->diffInHours(now());
        $timeText = $hoursRemaining < 24
            ? $this->movement->due_date->diffInHours(now()) . ' horas'
            : $this->movement->due_date->diffInDays(now()) . ' días';

        return (new MailMessage)
            ->subject('⏰ Recordatorio - Documento próximo a vencer - ' . $this->document->number)
            ->greeting('¡Atención!')
            ->line('Tiene un documento pendiente que vence pronto.')
            ->line('**Detalles del Documento:**')
            ->line('• Número: ' . $this->document->number)
            ->line('• Asunto: ' . ($this->document->subject ?? 'Sin asunto'))
            ->line('• Fecha límite: ' . $this->movement->due_date->format('d/m/Y H:i'))
            ->line('• Tiempo restante: ' . $timeText)
            ->line('• Prioridad: ' . $this->movement->priority_formatted)
            ->action('Ver Documento', url('/admin/documents/' . $this->document->id))
            ->line('Por favor, procese este documento antes de la fecha límite.');
    }

    public function toArray($notifiable)
    {
        $hoursRemaining = $this->movement->due_date->diffInHours(now());

        return [
            'document_id' => $this->document->id,
            'movement_id' => $this->movement->id,
            'type' => 'document_due_reminder',
            'priority' => $this->movement->priority,
            'message' => 'Documento ' . $this->document->number . ' vence pronto',
            'due_date' => $this->movement->due_date->format('d/m/Y H:i'),
            'hours_remaining' => $hoursRemaining,
            'url' => '/admin/documents/' . $this->document->id,
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
