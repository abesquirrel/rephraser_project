<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;
use App\Models\AuditLog;
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
            'keywords' => 'nullable|string',
            'is_template' => 'nullable|boolean',
            'category' => 'nullable|string'
        ]);

        // 1. Save to Database (Source of Truth)
        $entry = KnowledgeBase::create($validated);

        // Tier 2: Audit Logging
        AuditLog::create([
            'action' => 'Approve/Create',
            'original_content' => $validated['original_text'],
            'rephrased_content' => $validated['rephrased_text'],
            'user_name' => 'System' // Can be updated if auth is added later
        ]);

        // 2. Notify AI Service to rebuild index
        $this->triggerRebuild();

        return response()->json(['status' => 'success']);
    }

    public function suggestKeywords(Request $request)
    {
        $text = $request->input('text', '');
        $response = Http::post('http://ai:5001/suggest_keywords', ['text' => $text]);
        return $response->json();
    }

    public function getAuditLogs()
    {
        return AuditLog::orderBy('created_at', 'desc')->take(50)->get();
    }

    public function upload_kb(Request $request)
    {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->getRealPath();
            $file = fopen($path, 'r');
            $header = fgetcsv($file);
            
            // Check if first row is a header or data
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
            $keywords = isset($row[2]) ? trim($row[2]) : null;
            $isTemplate = isset($row[3]) ? filter_var($row[3], FILTER_VALIDATE_BOOLEAN) : false;
            $category = isset($row[4]) ? trim($row[4]) : null;
            
            // Skip if it looks like a header row
            if (strtolower($original) === 'original_text' || strtolower($rephrased) === 'rephrased_text') {
                return;
            }

            if ($original && $rephrased) {
                KnowledgeBase::create([
                    'original_text' => $original,
                    'rephrased_text' => $rephrased,
                    'keywords'      => $keywords,
                    'is_template'   => $isTemplate,
                    'category'      => $category
                ]);

                AuditLog::create([
                    'action' => 'Import',
                    'original_content' => $original,
                    'rephrased_content' => $rephrased
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
