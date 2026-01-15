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
        Schema::create('api_calls', function (Blueprint $table) {
            $table->id();
            $table->enum('service', ['inference', 'embedding', 'ollama', 'web_search']);
            $table->string('endpoint', 100);
            $table->string('method', 10);

            // Request
            $table->integer('request_payload_size')->nullable()->comment('Bytes');
            $table->timestamp('request_timestamp')->useCurrent();

            // Response
            $table->integer('response_status')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('response_payload_size')->nullable();

            // Cost tracking
            $table->decimal('estimated_cost_usd', 10, 6)->nullable();
            $table->integer('tokens_used')->nullable();

            // Error tracking
            $table->boolean('is_error')->default(false);
            $table->string('error_type', 50)->nullable();
            $table->text('error_message')->nullable();

            $table->index('service');
            $table->index('request_timestamp');
            $table->index('is_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_calls');
    }
};
