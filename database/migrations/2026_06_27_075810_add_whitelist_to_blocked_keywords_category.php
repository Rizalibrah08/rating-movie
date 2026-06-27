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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE blocked_keywords MODIFY category ENUM('spam', 'promosi', 'insult', 'slang_negative', 'whitelist', 'other') DEFAULT 'other'");
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE blocked_keywords MODIFY category ENUM('spam', 'promosi', 'insult', 'slang_negative', 'other') DEFAULT 'other'");
    }
};
