<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Document;
use Illuminate\Console\Command;
use App\Models\DocumentHistorie;

class CheckDocumentDueDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'app:check-document-due-dates';
    protected $signature = "documents:check-due-dates";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check document due dates and update priorities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documents = Document::where('status', '!=', 'completed')
            ->where('status', '!=', 'archived')
            ->whereNotNull('due_date')
            ->get();

        foreach ($documents as $document) {
            $daysRemaining = Carbon::now()->diffInDays($document->due_date, false);

            if ($daysRemaining <= 2 && $document->priority != 3) {
                $oldPriority = $document->priority;
                $document->update(['priority' => 3]);

                DocumentHistorie::create([
                    'document_id' => $document->id,
                    'action' => 'priority_changed',
                    'description' => "Prioridad cambiada a Muy Urgente (vencimiento próximo)",
                    'user_id' => 1, // Sistema
                    'changes' => [
                        'old_priority' => $oldPriority,
                        'new_priority' => 3,
                        'due_date' => $document->due_date
                    ]
                ]);
            } elseif ($daysRemaining <= 5 && $document->priority == 1) {
                $oldPriority = $document->priority;
                $document->update(['priority' => 2]);

                DocumentHistorie::create([
                    'document_id' => $document->id,
                    'action' => 'priority_changed',
                    'description' => "Prioridad cambiada a Urgente",
                    'user_id' => 1, // Sistema
                    'changes' => [
                        'old_priority' => $oldPriority,
                        'new_priority' => 2,
                        'due_date' => $document->due_date
                    ]
                ]);
            }
        }

        $this->info('Verificación de fechas de vencimiento completada.');
    }
}
