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
        Schema::create('user_actions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64);
            $table->enum('action_type', ['generate', 'approve', 'edit', 'delete', 'archive', 'search', 'model_change', 'setting_change']);
            $table->json('action_details')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('session_id')->references('session_id')->on('user_sessions')->onDelete('cascade');
            $table->index('action_type');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_actions');
    }
};
