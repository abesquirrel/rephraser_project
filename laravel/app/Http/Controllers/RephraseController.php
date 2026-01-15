<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class RephraseController extends Controller
{
    protected $embeddingServiceUrl = 'http://rephraser-ai-embedding:5002';
    protected $inferenceServiceUrl = 'http://rephraser-ai-inference:5001';

    private function aiCall()
    {
        return Http::withHeaders([
            'X-AI-KEY' => config('rephraser.ai_key', 'default_secret_key')
        ]);
    }

    private function sanitize($text)
    {
        // Basic protection against prompt injection: strip common markers
        return trim(strip_tags($text));
    }

    public function rephrase(Request $request)
    {
        $data = $request->all();
        $data['text'] = $this->sanitize($data['text'] ?? '');
        $data['negative_prompt'] = $this->sanitize($data['negative_prompt'] ?? '');

        // Stream response from Python service
        $response = $this->aiCall()
            ->withOptions(['stream' => true])
            ->post("{$this->inferenceServiceUrl}/rephrase", $data);

        return response()->stream(function () use ($response) {
            $body = $response->toPsrResponse()->getBody();
            while (!$body->eof()) {
                echo $body->read(1024);
                if (ob_get_level() > 0)
                    ob_flush();
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
            'id' => 'nullable|integer|exists:knowledge_bases,id',
            'original_text' => 'required|string',
            'rephrased_text' => 'required|string',
            'keywords' => 'nullable|string',
            'is_template' => 'nullable|boolean',
            'category' => 'nullable|string',
            'model_used' => 'nullable|string',
            'latency_ms' => 'nullable|integer',
            'temperature' => 'nullable|numeric',
            'max_tokens' => 'nullable|integer',
            'top_p' => 'nullable|numeric',
            'frequency_penalty' => 'nullable|numeric',
            'presence_penalty' => 'nullable|numeric'
        ]);

        // 1. Save or Update Database (Source of Truth)
        if (!empty($validated['id'])) {
            $entry = KnowledgeBase::find($validated['id']);
            $entry->update($validated);
            $action = 'Update';
        } else {
            $entry = KnowledgeBase::create($validated);
            $action = 'Approve/Create';
        }

        // Tier 2: Audit Logging
        AuditLog::create([
            'action' => $action,
            'original_content' => $validated['original_text'],
            'rephrased_content' => $validated['rephrased_text'],
            'model_used' => $validated['model_used'] ?? null,
            'latency_ms' => $validated['latency_ms'] ?? null,
            'temperature' => $validated['temperature'] ?? null,
            'max_tokens' => $validated['max_tokens'] ?? null,
            'top_p' => $validated['top_p'] ?? null,
            'frequency_penalty' => $validated['frequency_penalty'] ?? null,
            'presence_penalty' => $validated['presence_penalty'] ?? null,
            'user_name' => 'System' // Can be updated if auth is added later
        ]);

        // 2. Notify AI Service to rebuild index
        $this->triggerRebuild();

        return response()->json(['status' => 'success', 'id' => $entry->id]);
    }

    public function suggestKeywords(Request $request)
    {
        $text = $this->sanitize($request->input('text', ''));
        $response = $this->aiCall()->post("{$this->inferenceServiceUrl}/suggest_keywords", ['text' => $text]);
        return $response->json();
    }

    public function getAuditLogs()
    {
        return AuditLog::orderBy('created_at', 'desc')->take(50)->get();
    }

    public function getModels()
    {
        try {
            $response = $this->aiCall()->get("{$this->inferenceServiceUrl}/list_models");
            return $response->json();
        } catch (\Exception $e) {
            Log::error("Failed to fetch models: " . $e->getMessage());
            return response()->json(['models' => [], 'error' => 'Service Unavailable']);
        }
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
            $validated = $request->validate([
                'original_text' => 'required|string',
                'rephrased_text' => 'required|string',
                'keywords' => 'nullable|string',
                'is_template' => 'nullable|boolean',
                'category' => 'nullable|string',
            ]);
            KnowledgeBase::create($validated);
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
                    'keywords' => $keywords,
                    'is_template' => $isTemplate,
                    'category' => $category
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
            $this->aiCall()->post("{$this->embeddingServiceUrl}/trigger_rebuild");
        } catch (\Exception $e) {
            Log::error("Failed to notify AI service: " . $e->getMessage());
        }
    }
}
