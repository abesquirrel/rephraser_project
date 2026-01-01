<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\KnowledgeBase;

class KnowledgeBaseSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('knowledge_base.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found at: $csvFile");
            return;
        }

        $data = array_map('str_getcsv', file($csvFile));
        $header = array_shift($data); // Remove header

        $count = 0;
        foreach ($data as $row) {
            if (count($row) >= 2) {
                // Ensure we handle basic quoting/encoding if needed, but str_getcsv should handle standard CSV
                // Some rows had trailing empties in the 'head' output, so we take index 0 and 1
                $original = trim($row[0]);
                $rephrased = trim($row[1]);

                if ($original && $rephrased) {
                    KnowledgeBase::create([
                        'original_text' => $original,
                        'rephrased_text' => $rephrased,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $count++;
                }
            }
        }
        $this->command->info("Imported $count entries from CSV.");
    }
}
