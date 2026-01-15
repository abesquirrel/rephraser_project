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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id', 64)->unique();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_generations')->default(0);
            $table->integer('total_approvals')->default(0);
            $table->integer('total_edits')->default(0);
            $table->integer('total_web_searches')->default(0);
            $table->integer('avg_generation_time_ms')->nullable();
            $table->string('theme', 10)->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
