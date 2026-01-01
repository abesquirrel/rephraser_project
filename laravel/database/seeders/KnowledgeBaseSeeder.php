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

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Remove header

        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) >= 2) {
                $original = trim($row[0]);
                $rephrased = trim($row[1]);

                if ($original && $rephrased) {
                    KnowledgeBase::create([
                        'original_text' => $original,
                        'rephrased_text' => $rephrased,
                    ]);
                    $count++;
                }
            }
        }
        fclose($file);
        $this->command->info("Imported $count entries from CSV.");
    }
}
