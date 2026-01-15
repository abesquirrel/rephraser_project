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
        if (!Schema::hasTable('error_logs')) {
            Schema::create('error_logs', function (Blueprint $table) {
                $table->id();
                $table->enum('severity', ['debug', 'info', 'warning', 'error', 'critical']);
                $table->string('source', 100);
                $table->string('error_code', 50)->nullable();
                $table->text('error_message');
                $table->text('stack_trace')->nullable();

                // Context
                $table->string('user_session_id', 64)->nullable();
                $table->string('request_url')->nullable();
                $table->string('request_method', 10)->nullable();
                $table->json('request_payload')->nullable();

                // Environment
                $table->string('php_version', 20)->nullable();
                $table->string('laravel_version', 20)->nullable();
                $table->text('browser_agent')->nullable();

                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();

                $table->foreign('user_session_id')->references('session_id')->on('user_sessions')->onDelete('set null');
                $table->index('severity');
                $table->index('source');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
