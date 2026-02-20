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
        Schema::table('model_generations', function (Blueprint $table) {
            $table->text('original_text')->nullable()->after('model_display_name');
            $table->text('rephrased_text')->nullable()->after('original_text');
            $table->string('category')->nullable()->after('template_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_generations', function (Blueprint $table) {
            $table->dropColumn(['original_text', 'rephrased_text', 'category']);
        });
    }
};
