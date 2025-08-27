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
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('document_movement_id')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->enum('attachment_type', ['original', 'response', 'annex', 'support', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('document_movement_id')->references('id')->on('document_movements')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['document_id', 'attachment_type']);
            $table->index('document_movement_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
    }
};
