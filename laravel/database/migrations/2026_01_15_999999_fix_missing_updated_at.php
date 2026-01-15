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
        if (Schema::hasTable('model_generations')) {
            Schema::table('model_generations', function (Blueprint $table) {
                if (!Schema::hasColumn('model_generations', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('error_logs')) {
            Schema::table('error_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('error_logs', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('model_generations')) {
            Schema::table('model_generations', function (Blueprint $table) {
                if (Schema::hasColumn('model_generations', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }

        if (Schema::hasTable('error_logs')) {
            Schema::table('error_logs', function (Blueprint $table) {
                if (Schema::hasColumn('error_logs', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
    }
};
