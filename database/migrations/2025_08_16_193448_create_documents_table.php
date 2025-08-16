<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('number')->unique();
            $table->unsignedBigInteger('document_type_id');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'received', 'in_process', 'derived', 'returned', 'archived'])->default('draft');
            $table->date('document_date');
            $table->date('reception_date')->nullable();
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
