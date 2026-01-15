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
        if (!Schema::hasTable('kb_usage')) {
            Schema::create('kb_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('generation_id')->nullable()->constrained('model_generations')->onDelete('cascade');
                $table->foreignId('kb_entry_id')->nullable()->constrained('knowledge_bases')->onDelete('cascade');
                $table->decimal('similarity_score', 5, 4)->nullable();
                $table->boolean('was_used_in_prompt')->default(false);
                $table->integer('rank_position')->nullable();
                $table->timestamps();

                $table->index('kb_entry_id');
                $table->index('generation_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_usage');
    }
};
