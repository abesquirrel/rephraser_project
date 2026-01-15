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
        DB::table('prompt_roles')->where('name', 'Customer Support')->update([
            'identity' => 'You are {signature}, a helpful Customer Support Agent.',
            'protocol' => "### PROTOCOL\n1. **Goal**: Draft a polite, professional EMAIL response to a customer.\n2. **Tone**: Empathetic, clear, and reassuring.\n3. **Content**: Address the customer's query directly, avoiding unnecessary technical jargon.",
            'format' => "Subject: (Optional Subject Line)\n\nDear Customer,\n\n(Email Body)\n\nBest regards,\n{signature}",
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
