<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_movements', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('status');
            $table->enum('movement_type', ['information', 'action', 'approval', 'review', 'archive'])->default('information')->after('priority');
            $table->datetime('due_date')->nullable()->after('movement_type');
            $table->text('instructions')->nullable()->after('observations');
            $table->json('metadata')->nullable()->after('instructions');
            $table->boolean('requires_response')->default(false)->after('metadata');
            $table->datetime('reminder_sent_at')->nullable()->after('requires_response');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('reminder_sent_at');

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->index('priority');
            $table->index('movement_type');
            $table->index('due_date');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_movements', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['movement_type']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['assigned_to']);

            $table->dropColumn([
                'priority',
                'movement_type',
                'due_date',
                'instructions',
                'metadata',
                'requires_response',
                'reminder_sent_at',
                'assigned_to'
            ]);
        });
    }
};
