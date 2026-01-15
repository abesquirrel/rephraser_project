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
        if (Schema::hasTable('knowledge_bases') && !Schema::hasColumn('knowledge_bases', 'category')) {
            Schema::table('knowledge_bases', function (Blueprint $table) {
                $table->string('category')->nullable()->after('is_template');
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('action');
                $table->text('original_content')->nullable();
                $table->text('rephrased_content')->nullable();
                $table->string('user_name')->default('System');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
