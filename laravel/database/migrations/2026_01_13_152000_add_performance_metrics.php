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
        if (Schema::hasTable('knowledge_bases') && !Schema::hasColumn('knowledge_bases', 'latency_ms')) {
            Schema::table('knowledge_bases', function (Blueprint $table) {
                $table->integer('latency_ms')->nullable()->after('model_used');
                $table->float('temperature')->nullable()->after('latency_ms');
                $table->integer('max_tokens')->nullable()->after('temperature');
                $table->float('top_p')->nullable()->after('max_tokens');
                $table->float('frequency_penalty')->nullable()->after('top_p');
                $table->float('presence_penalty')->nullable()->after('frequency_penalty');
            });
        }

        if (Schema::hasTable('audit_logs') && !Schema::hasColumn('audit_logs', 'latency_ms')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->integer('latency_ms')->nullable()->after('model_used');
                $table->float('temperature')->nullable()->after('latency_ms');
                $table->integer('max_tokens')->nullable()->after('temperature');
                $table->float('top_p')->nullable()->after('max_tokens');
                $table->float('frequency_penalty')->nullable()->after('top_p');
                $table->float('presence_penalty')->nullable()->after('frequency_penalty');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn([
                'latency_ms',
                'temperature',
                'max_tokens',
                'top_p',
                'frequency_penalty',
                'presence_penalty'
            ]);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'latency_ms',
                'temperature',
                'max_tokens',
                'top_p',
                'frequency_penalty',
                'presence_penalty'
            ]);
        });
    }
};
