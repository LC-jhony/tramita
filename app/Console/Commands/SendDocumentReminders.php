<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\DocumentMovement;
use App\Notifications\DocumentDueReminderNotification;
use Illuminate\Console\Command;


class SendDocumentReminders extends Command
{
    protected $signature = 'documents:send-reminders';
    protected $description = 'Enviar recordatorios de documentos próximos a vencer';

    public function handle()
    {
        $this->info('Enviando recordatorios de documentos próximos a vencer...');

        // Buscar movimientos que vencen en las próximas 24 horas
        $dueSoonMovements = DocumentMovement::with(['document', 'toArea', 'assignedTo'])
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addHours(24))
            ->whereNull('reminder_sent_at')
            ->get();

        $remindersSent = 0;

        foreach ($dueSoonMovements as $movement) {
            // Determinar a quién enviar el recordatorio
            $users = collect();

            if ($movement->assigned_to) {
                $users->push(User::find($movement->assigned_to));
            } else {
                // Enviar a todos los usuarios del área destino
                $users = User::where('area_id', $movement->to_area_id)->get();
            }

            // Enviar notificaciones
            foreach ($users as $user) {
                if ($user) {
                    $user->notify(new DocumentDueReminderNotification($movement->document, $movement));
                    $remindersSent++;
                }
            }

            // Marcar como recordatorio enviado
            $movement->update(['reminder_sent_at' => now()]);
        }

        $this->info("Se enviaron {$remindersSent} recordatorios para {$dueSoonMovements->count()} documentos.");

        // Buscar documentos vencidos
        $overdueMovements = DocumentMovement::with(['document', 'toArea', 'assignedTo'])
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->get();

        if ($overdueMovements->count() > 0) {
            $this->warn("¡Atención! Hay {$overdueMovements->count()} documentos vencidos:");

            foreach ($overdueMovements as $movement) {
                $daysOverdue = $movement->due_date->diffInDays(now());
                $this->line("- Documento {$movement->document->number} (Vencido hace {$daysOverdue} días)");
            }
        }

        return Command::SUCCESS;
    }
}
