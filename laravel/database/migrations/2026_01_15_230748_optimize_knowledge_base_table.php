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
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // LONGBLOB is needed for storing binary vector data
            $table->binary('embedding')->nullable()->after('category');
            $table->unsignedInteger('hits')->default(0)->after('embedding');
            $table->timestamp('last_used_at')->nullable()->after('hits');
        });

        // Use raw statement to ensure LONGBLOB if using MySQL/MariaDB as 'binary' might default to BLOB (64kb) which is too small for some vectors? 
        // Actually, default BLOB is 65KB. A 384-dim float32 vector is 384 * 4 bytes = 1536 bytes. BLOB is plenty.
        // But let's stick to standard Larvel migration which maps binary to BLOB nicely.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn(['embedding', 'hits', 'last_used_at']);
        });
    }
};
