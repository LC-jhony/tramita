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
        Schema::create('document_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('from_area_id');
            $table->unsignedBigInteger('to_area_id');
            $table->unsignedBigInteger('user_id');
            $table->text('observations')->nullable();
            $table->enum('status', ['pending', 'received', 'rejected', 'processed'])->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('from_area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->foreign('to_area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_movements');
    }
};
