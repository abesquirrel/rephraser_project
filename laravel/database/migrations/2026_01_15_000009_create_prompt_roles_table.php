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
        Schema::create('prompt_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('identity');
            $table->text('protocol');
            $table->text('format');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Seed default roles
        DB::table('prompt_roles')->insert([
            [
                'name' => 'Tech Support',
                'identity' => 'You are {signature}. Technical Support Specialist.',
                'protocol' => "### PROTOCOL\n1. **Audience**: You are writing to a colleague or customer requiring detailed technical context.\n2. **Analyze**: Identify the core issue, actions taken, and next steps.\n3. **Format**: STICK STRICTLY to the required section headers.",
                'format' => "Hello,\n\nObservations: (Details of the issue observed, potential problems, and diagnosis)\n\nActions taken: (Active actions performed to fix/correct/improve. Leave empty if none)\n\nRecommendations: (Suggestions for the customer, preventive measures, or expected customer actions)\n\nRegards,\n{signature}",
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Customer Support',
                'identity' => 'You are {signature}. Customer Support Representative.',
                'protocol' => "### PROTOCOL\n1. **Audience**: You are writing to an END USER. Be polite, empathetic, and clear.\n2. **Focus**: Reassure the customer and explain things simply.\n3. **Format**: Standard professional letter format.",
                'format' => "Hello,\n\n(Rephrased content politely explaining the situation and resolution)\n\nRegards,\n{signature}",
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_roles');
    }
};
