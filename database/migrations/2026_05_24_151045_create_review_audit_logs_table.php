<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('movie_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('review_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_triggered', 60)->nullable();   // mis. 'blacklist_keyword'
            $table->enum('action', ['published', 'pending', 'rejected'])->index();
            $table->string('reason', 500)->nullable();
            $table->text('payload_excerpt')->nullable();        // 200 char body excerpt
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['rule_triggered', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_audit_logs');
    }
};
