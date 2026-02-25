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
        Schema::dropIfExists('kb_usage');
        Schema::dropIfExists('api_calls');
        Schema::dropIfExists('model_generations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible since data is gone
    }
};
