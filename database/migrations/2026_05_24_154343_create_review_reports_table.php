<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reason', ['spam', 'offensive', 'misleading', 'other'])->default('other');
            $table->string('note', 500)->nullable();
            $table->enum('status', ['pending', 'resolved_hide', 'resolved_keep'])->default('pending');
            $table->timestamps();

            $table->unique(['review_id', 'reporter_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reports');
    }
};
