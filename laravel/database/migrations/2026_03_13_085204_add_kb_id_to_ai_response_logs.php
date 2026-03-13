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
        Schema::table('ai_response_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('knowledge_base_id')->nullable()->after('id');
            $table->foreign('knowledge_base_id')->references('id')->on('knowledge_bases')->onDelete('set null');
            
            // Note: was_approved already exists from previous migration but let's ensure it's there
            // if (Schema::hasColumn('ai_response_logs', 'was_approved')) { ... }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_response_logs', function (Blueprint $table) {
            $table->dropForeign(['knowledge_base_id']);
            $table->dropColumn('knowledge_base_id');
        });
    }
};
