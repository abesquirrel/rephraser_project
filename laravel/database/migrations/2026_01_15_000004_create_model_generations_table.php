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
        if (!Schema::hasTable('model_generations')) {
            Schema::create('model_generations', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 64)->nullable();
                $table->string('model_id', 100);
                $table->string('model_display_name', 100)->nullable();

                // Request metadata
                $table->integer('input_text_length')->nullable();
                $table->integer('output_text_length')->nullable();
                $table->integer('prompt_tokens')->nullable();
                $table->integer('completion_tokens')->nullable();
                $table->integer('total_tokens')->nullable();

                // Performance
                $table->integer('generation_time_ms');
                $table->boolean('was_approved')->default(false);
                $table->boolean('was_edited')->default(false);
                $table->integer('edit_distance')->nullable();

                // Configuration
                $table->decimal('temperature', 3, 2)->nullable();
                $table->integer('max_tokens')->nullable();
                $table->integer('kb_count')->nullable();
                $table->boolean('web_search_enabled')->default(false);
                $table->boolean('template_mode')->default(false);

                // Outcome
                $table->boolean('error_occurred')->default(false);
                $table->text('error_message')->nullable();

                $table->timestamps();

                $table->foreign('session_id')->references('session_id')->on('user_sessions')->onDelete('set null');
                $table->index('model_id');
                $table->index('was_approved');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_generations');
    }
};
