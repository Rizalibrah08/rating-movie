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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis');
            $table->smallInteger('year');
            $table->unsignedSmallInteger('duration_min')->nullable();
            $table->string('director')->nullable();
            // Poster: salah satu wajib (file atau URL) — divalidasi di FormRequest
            $table->string('poster_path')->nullable();
            $table->string('poster_url')->nullable();
            // Backdrop: opsional, dual mode juga
            $table->string('backdrop_path')->nullable();
            $table->string('backdrop_url')->nullable();
            $table->timestamps();

            $table->index('year');
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
