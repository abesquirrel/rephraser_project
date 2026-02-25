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
        Schema::create('ai_response_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('model_id');
            $table->string('model_display_name')->nullable();

            $table->text('original_text')->nullable();
            $table->integer('input_text_length')->default(0);

            $table->text('rephrased_text')->nullable();
            $table->integer('output_text_length')->default(0);

            $table->decimal('temperature', 5, 2)->nullable();
            $table->integer('max_tokens')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();

            $table->integer('generation_time_ms')->nullable();

            $table->boolean('web_search_enabled')->default(false);
            $table->boolean('template_mode')->default(false);
            $table->string('category')->nullable();
            $table->integer('kb_count')->nullable();

            $table->boolean('was_approved')->default(false);
            $table->boolean('was_edited')->default(false);
            $table->integer('edit_distance')->nullable();

            $table->json('meta_data')->nullable();
            $table->boolean('is_error')->default(false);
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_response_logs');
    }
};
