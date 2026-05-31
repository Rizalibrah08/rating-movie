<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 120);
            $table->enum('category', ['spam', 'promosi', 'insult', 'slang_negative', 'other'])->default('other');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('keyword');
            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_keywords');
    }
};
