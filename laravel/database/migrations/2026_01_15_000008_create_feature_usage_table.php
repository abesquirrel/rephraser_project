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
        Schema::create('feature_usage', function (Blueprint $table) {
            $table->id();
            $table->string('feature_name', 100);
            $table->integer('usage_count')->default(1);
            $table->integer('unique_sessions')->default(1);
            $table->date('date');
            $table->timestamps();

            $table->unique(['feature_name', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_usage');
    }
};
