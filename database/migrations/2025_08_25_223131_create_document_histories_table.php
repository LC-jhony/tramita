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
        Schema::create('document_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('action');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('area_id');
            $table->unsignedBigInteger('user_id');
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index('action');
            $table->index('created_at');

            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_histories');
    }
};
