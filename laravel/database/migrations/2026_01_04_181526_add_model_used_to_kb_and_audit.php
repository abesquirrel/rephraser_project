<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->string('model_used')->nullable()->after('category');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('model_used')->nullable()->after('rephrased_content');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn('model_used');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('model_used');
        });
    }
};
