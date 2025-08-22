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
        Schema::create('gestions', function (Blueprint $table) {
            $table->uuid();
            $table->integer('start_year');
            $table->integer('end_year');
            $table->string('name');
            $table->string('namagement');
            $table->boolean('active')->default(true);
            $table->timestamps();
            // Gantizamos que no haya solapamiento de periodo
            $table->unique(['start_year', 'end_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestions');
    }
};
