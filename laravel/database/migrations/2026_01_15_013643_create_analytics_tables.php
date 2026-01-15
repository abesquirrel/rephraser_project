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
        Schema::disableForeignKeyConstraints();

        // 1. User Sessions
        Schema::dropIfExists('user_sessions');
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->unique();
            $table->string('user_signature')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_generations')->default(0);
            $table->integer('total_approvals')->default(0);
            $table->integer('total_edits')->default(0);
            $table->integer('total_web_searches')->default(0);
            $table->integer('avg_generation_time_ms')->nullable();
            $table->string('theme', 10)->nullable();
            $table->timestamps();
        });

        // 2. Model Generations
        Schema::dropIfExists('model_generations');
        Schema::create('model_generations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->nullable();
            $table->string('model_id', 100);
            $table->string('model_display_name', 100)->nullable();

            // Request Metadata
            $table->integer('input_text_length')->nullable();
            $table->integer('output_text_length')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();

            // Performance
            $table->integer('generation_time_ms')->nullable(); // Can be null if failed
            $table->boolean('was_approved')->default(false);
            $table->boolean('was_edited')->default(false);
            $table->integer('edit_distance')->nullable();

            // Configuration
            $table->decimal('temperature', 3, 2)->nullable();
            $table->integer('max_tokens')->nullable();
            $table->integer('kb_count')->nullable();
            $table->boolean('web_search_enabled')->nullable();
            $table->boolean('template_mode')->nullable();

            // Outcome
            $table->boolean('error_occurred')->default(false);
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->foreign('session_id')->references('session_id')->on('user_sessions')->nullOnDelete();
            $table->index('model_id');
            $table->index('was_approved');
        });

        // 3. API Calls
        Schema::dropIfExists('api_calls');
        Schema::create('api_calls', function (Blueprint $table) {
            $table->id();
            $table->enum('service', ['inference', 'embedding', 'ollama', 'web_search']);
            $table->string('endpoint', 100);
            $table->string('method', 10);

            $table->integer('request_payload_size')->nullable();
            $table->timestamp('request_timestamp')->useCurrent();

            $table->integer('response_status')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('response_payload_size')->nullable();

            $table->decimal('estimated_cost_usd', 10, 6)->nullable();
            $table->integer('tokens_used')->nullable();

            $table->boolean('is_error')->default(false);
            $table->string('error_type', 50)->nullable();
            $table->text('error_message')->nullable();

            $table->index('service');
            $table->index('request_timestamp');
            $table->index('is_error');
        });

        // 4. User Actions
        Schema::dropIfExists('user_actions');
        Schema::create('user_actions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->nullable();
            $table->string('action_type'); // Enum handled as string for flexibility
            $table->json('action_details')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('session_id')->references('session_id')->on('user_sessions')->cascadeOnDelete();
            $table->index('action_type');
            $table->index('timestamp');
        });

        // 5. Error Logs
        Schema::dropIfExists('error_logs');
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('severity')->index(); // debug, info, warning, error, critical
            $table->string('source', 100)->index();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->text('stack_trace')->nullable();

            $table->string('user_session_id', 64)->nullable();
            $table->string('request_url')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->json('request_payload')->nullable();

            $table->string('php_version', 20)->nullable();
            $table->string('laravel_version', 20)->nullable();
            $table->text('browser_agent')->nullable();

            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('user_actions');
        Schema::dropIfExists('api_calls');
        Schema::dropIfExists('model_generations');
        Schema::dropIfExists('user_sessions');
        Schema::enableForeignKeyConstraints();
    }
};
