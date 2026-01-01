<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Log;

class RephraseController extends Controller
{
    protected $aiServiceUrl = 'http://rephraser-ai:5001';

    public function rephrase(Request $request)
    {
        // Stream response from Python service
        $response = Http::withOptions(['stream' => true])
            ->post("{$this->aiServiceUrl}/rephrase", $request->all());

        return response()->stream(function () use ($response) {
            $body = $response->toPsrResponse()->getBody();
            while (!$body->eof()) {
                echo $body->read(1024);
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // Nginx specific
        ]);
    }

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'original_text' => 'required|string',
            'rephrased_text' => 'required|string',
        ]);

        // 1. Save to Database (Source of Truth)
        KnowledgeBase::create($validated);

        // 2. Notify AI Service to rebuild index (or add single entry)
        // We send the new entry so Python doesn't have to query DB immediately if not needed,
        // but for full consistency, triggering a rebuild is safer.
        try {
            Http::post("{$this->aiServiceUrl}/trigger_rebuild");
        } catch (\Exception $e) {
            Log::error("Failed to notify AI service: " . $e->getMessage());
            // We don't fail the request because DB save succeeded.
        }

        return response()->json(['status' => 'success']);
    }

    public function upload_kb(Request $request)
    {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->getRealPath();
            $file = fopen($path, 'r');
            $header = fgetcsv($file);
            
            // Check if first row is a header or data
            // If it contains "original_text" it's definitely a header
            $isHeader = false;
            if ($header && (stripos($header[0], 'original') !== false || stripos($header[1], 'rephrased') !== false)) {
                $isHeader = true;
            }

            if (!$isHeader && $header) {
                // If not header, process it as data
                $this->storeKnowledgeEntry($header);
            }

            while (($row = fgetcsv($file)) !== false) {
                $this->storeKnowledgeEntry($row);
            }
            fclose($file);
        } elseif ($request->has('original_text')) {
             KnowledgeBase::create($request->all());
        }

        // Notify AI
        $this->triggerRebuild();

        return response()->json(['status' => 'success']);
    }

    private function storeKnowledgeEntry($row)
    {
        if (count($row) >= 2) {
            $original = trim($row[0]);
            $rephrased = trim($row[1]);
            
            // Skip if it looks like a header row
            if (strtolower($original) === 'original_text' || strtolower($rephrased) === 'rephrased_text') {
                return;
            }

            if ($original && $rephrased) {
                KnowledgeBase::create([
                    'original_text' => $original,
                    'rephrased_text' => $rephrased
                ]);
            }
        }
    }

    private function triggerRebuild()
    {
        try {
            Http::post("{$this->aiServiceUrl}/trigger_rebuild");
        } catch (\Exception $e) {
            Log::error("Failed to notify AI service: " . $e->getMessage());
        }
    }
}
