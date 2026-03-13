<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kb:export {--format=json} {--category=}', function () {
    $format = $this->option('format');
    $category = $this->option('category');

    $query = \App\Models\KnowledgeBase::query();
    if ($category) {
        $query->where('category', $category);
    }

    $entries = $query->get();

    if ($entries->isEmpty()) {
        $this->error('No entries found to export.');
        return;
    }

    $timestamp = now()->format('Y-m-d_H-i-s');
    $filename = "kb_export_{$timestamp}.{$format}";
    $path = storage_path("app/exports/{$filename}");

    if (!is_dir(storage_path('app/exports'))) {
        mkdir(storage_path('app/exports'), 0755, true);
    }

    if ($format === 'json') {
        file_put_contents($path, $entries->toJson(JSON_PRETTY_PRINT));
    } elseif ($format === 'csv') {
        $handle = fopen($path, 'w');
        // Headers
        fputcsv($handle, ['id', 'original_text', 'rephrased_text', 'category', 'keywords', 'role', 'is_template', 'created_at']);
        
        foreach ($entries as $entry) {
            fputcsv($handle, [
                $entry->id,
                $entry->original_text,
                $entry->rephrased_text,
                $entry->category,
                $entry->keywords,
                $entry->role,
                $entry->is_template ? 'YES' : 'NO',
                $entry->created_at
            ]);
        }
        fclose($handle);
    } else {
        $this->error("Unsupported format: {$format}");
        return;
    }

    $this->info("Successfully exported " . $entries->count() . " entries to: {$path}");
})->purpose('Export knowledge base entries for RAG or analysis');
