<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use App\Models\ApiCall;
use App\Models\KbUsage;

class RephraseController extends Controller
{
    protected $embeddingServiceUrl = 'http://rephraser-ai-embedding:5002';
    protected $inferenceServiceUrl = 'http://rephraser-ai-inference:5001';

    private function aiCall()
    {
        return Http::withHeaders([
            'X-AI-KEY' => config('rephraser.ai_key', 'default_secret_key')
        ])->timeout(300);
    }

    private function sanitize($text)
    {
        // Basic protection against prompt injection: strip common markers
        return trim(strip_tags($text));
    }

    public function rephrase(Request $request)
    {
        $data = $request->all();
        $inputLength = strlen($data['text'] ?? '');
        $data['text'] = $this->sanitize($data['text'] ?? '');
        $data['negative_prompt'] = $this->sanitize($data['negative_prompt'] ?? '');

        // --- Dynamic Role Lookup ---
        $roleName = $data['role'] ?? null;

        if ($roleName) {
            $roleConfig = \App\Models\PromptRole::where('name', $roleName)->first();
        } else {
            $roleConfig = \App\Models\PromptRole::where('is_default', true)->first();
        }

        // Pass dynamic config if found
        if ($roleConfig) {
            $data['role_config'] = [
                'identity' => $roleConfig->identity,
                'protocol_override' => $roleConfig->protocol,
                'format_override' => $roleConfig->format
            ];
            // Also pass the name just in case the AI needs it for logging
            $data['role'] = $roleConfig->name;
        }

        // Resolve Session ID safely
        $sessionId = $request->header('X-Session-ID');
        if (empty($sessionId) || $sessionId === 'null' || !$sessionId) {
            $sessionId = session()->getId();
        }

        // Ensure the session ID actually exists in the DB to avoid FK errors (1452)
        // Instead of nulling it, we create a stub if it doesn't exist yet
        if ($sessionId) {
            \App\Models\UserSession::firstOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_signature' => $request->input('signature') ?? 'Lazy Init',
                    'last_active_at' => now()
                ]
            );
        }

        // 1. Create Log Record
        $generationLog = \App\Models\ModelGeneration::create([
            'session_id' => $sessionId,
            'model_id' => $data['model'] ?? 'unknown',
            'model_display_name' => $this->formatModelName($data['model'] ?? 'unknown'),
            'input_text_length' => $inputLength,
            'temperature' => $data['temperature'] ?? null,
            'max_tokens' => $data['max_tokens'] ?? null,
            'kb_count' => $data['kb_count'] ?? null,
            'web_search_enabled' => $data['web_search_enabled'] ?? false,
            'template_mode' => $data['template_mode'] ?? false,
            'prompt_tokens' => (int) ($inputLength / 3), // Approx 3-4 chars per token
        ]);

        $startTime = microtime(true);

        // 2. Log API Call Start
        $apiCall = ApiCall::create([
            'service' => 'inference',
            'endpoint' => "{$this->inferenceServiceUrl}/rephrase",
            'method' => 'POST',
            'request_payload_size' => strlen(json_encode($data)),
            'tokens_used' => (int) ($inputLength / 3),
        ]);

        // Stream response from Python service
        try {
            $response = $this->aiCall()
                ->withOptions(['stream' => true])
                ->post("{$this->inferenceServiceUrl}/rephrase", $data);

            $apiCall->update(['response_status' => $response->status()]);
        } catch (\Exception $e) {
            $apiCall->update([
                'is_error' => true,
                'error_type' => 'Connection Error',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }

        return response()->stream(function () use ($response, $generationLog, $startTime, $apiCall) {
            try {
                $body = $response->toPsrResponse()->getBody();
                $accumulatedOutput = '';

                while (!$body->eof()) {
                    $chunk = $body->read(1024);
                    if (empty($chunk))
                        continue;

                    $accumulatedOutput .= $chunk;
                    echo $chunk;

                    if (ob_get_level() > 0)
                        ob_flush();
                    flush();
                }

                // 2. Update Log on Completion
                $duration = (microtime(true) - $startTime) * 1000;

                // Search backwards for the metadata line
                $lines = explode("\n", trim($accumulatedOutput));
                $parsedMeta = [];
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    $line = trim($lines[$i]);
                    if (empty($line))
                        continue;

                    $p = json_decode($line, true);
                    if (isset($p['meta']) || isset($p['data'])) {
                        $parsedMeta = $p;
                        break;
                    }
                }

                $finalContent = $parsedMeta['data'] ?? '';
                $kbIds = $parsedMeta['meta']['kb_ids'] ?? [];
                $outputLength = strlen($finalContent);

                $generationLog->update([
                    'generation_time_ms' => (int) $duration,
                    'output_text_length' => $outputLength,
                    'completion_tokens' => (int) ($outputLength / 4),
                    'total_tokens' => (int) ($outputLength / 4) + ($generationLog->prompt_tokens ?? 0)
                ]);

                $apiCall->update([
                    'response_time_ms' => (int) $duration,
                    'response_payload_size' => strlen($accumulatedOutput)
                ]);

                // 3. Log KB Usage
                if (!empty($kbIds)) {
                    foreach ($kbIds as $kbId) {
                        KbUsage::create([
                            'generation_id' => $generationLog->id,
                            'kb_entry_id' => $kbId,
                            'was_used_in_prompt' => true
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Stream processing error: " . $e->getMessage());
                // Silently fail to avoid sending HTML error page into the JSON stream
                $apiCall->update([
                    'is_error' => true,
                    'error_message' => 'Stream Interrupted: ' . $e->getMessage()
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // Nginx specific
        ]);
    }

    // --- Role Management Logic ---

    public function getRoles()
    {
        return response()->json(\App\Models\PromptRole::all());
    }

    public function saveRole(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:prompt_roles,id',
            'name' => 'required|string|max:50',
            'identity' => 'required|string',
            'protocol' => 'required|string',
            'format' => 'required|string',
            'is_default' => 'boolean'
        ]);

        // Enforce exclusivity for is_default
        if (!empty($validated['is_default']) && $validated['is_default']) {
            \App\Models\PromptRole::where('is_default', true)->update(['is_default' => false]);
        }

        if (isset($validated['id'])) {
            $role = \App\Models\PromptRole::find($validated['id']);
            $role->update($validated);
        } else {
            $role = \App\Models\PromptRole::create($validated);
        }

        return response()->json(['status' => 'success', 'role' => $role]);
    }

    public function deleteRole($id)
    {
        $role = \App\Models\PromptRole::find($id);
        if ($role) {
            // Protect Tech Support specifically as per requirements
            if ($role->name === 'Tech Support') {
                return response()->json(['status' => 'error', 'message' => 'The Tech Support role cannot be deleted.'], 400);
            }
            if ($role->is_default) {
                return response()->json(['status' => 'error', 'message' => 'Cannot delete the default role. Please set another role as default first.'], 400);
            }
            $role->delete();
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error', 'message' => 'Role not found'], 404);
    }

    // End Role Logic

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:knowledge_bases,id',
            'original_text' => 'required|string',
            'rephrased_text' => 'required|string',
            'keywords' => 'nullable|string',
            'is_template' => 'nullable|boolean',
            'category' => 'nullable|string',
            'role' => 'nullable|string|max:100',
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

        // Calculate Edit Distance
        $editDist = 0;
        if (!empty($validated['original_text']) && !empty($validated['rephrased_text'])) {
            $editDist = levenshtein($validated['original_text'], $validated['rephrased_text']);
        }

        // Try to link back to the ModelGeneration to update metrics
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();

        if ($sessionId) {
            $latestGen = \App\Models\ModelGeneration::where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestGen) {
                $latestGen->update([
                    'was_approved' => true,
                    'edit_distance' => $editDist
                ]);
            }
        }

        // 2. Notify AI Service to rebuild index
        $this->notifyAiRebuild();

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
        // Notify AI
        $this->notifyAiRebuild();

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

    public function getKbStats()
    {
        $total = KnowledgeBase::count();
        $latest = KnowledgeBase::select('updated_at')->latest()->first();

        $byCategory = KnowledgeBase::whereNotNull('category')
            ->where('category', '!=', '')
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_entries' => $total,
            'last_updated' => $latest ? $latest->updated_at : null,
            'category_breakdown' => $byCategory
        ]);
    }

    public function startSession(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:64',
            'user_signature' => 'nullable|string|max:255',
            'theme' => 'nullable|string|max:10'
        ]);

        $session = \App\Models\UserSession::firstOrCreate(
            ['session_id' => $validated['session_id']],
            [
                'user_signature' => $validated['user_signature'] ?? null,
                'theme' => $validated['theme'] ?? 'dark',
                'started_at' => now(),
            ]
        );

        // Update if exists (e.g. signature changed)
        if (!$session->wasRecentlyCreated) {
            $session->update([
                'user_signature' => $validated['user_signature'] ?? $session->user_signature,
                'theme' => $validated['theme'] ?? $session->theme
            ]);
        }

        return response()->json(['message' => 'Session tracked', 'id' => $session->id]);
    }

    public function logAction(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'action_type' => 'required|string',
            'action_details' => 'nullable|array'
        ]);

        try {
            \App\Models\UserAction::create([
                'session_id' => $validated['session_id'],
                'action_type' => $validated['action_type'],
                'action_details' => $validated['action_details']
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('Failed to log action: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function notifyAiRebuild()
    {
        try {
            $this->aiCall()->post("{$this->embeddingServiceUrl}/trigger_rebuild");
        } catch (\Exception $e) {
            Log::error("Failed to notify AI service: " . $e->getMessage());
        }
    }
    // --- Optimization Endpoints ---

    public function triggerRebuild(Request $request)
    {
        try {
            $response = $this->aiCall()->post("{$this->embeddingServiceUrl}/trigger_rebuild");
            return $response->json();
        } catch (\Exception $e) {
            Log::error("Failed to trigger rebuild: " . $e->getMessage());
            return response()->json(['error' => 'Service Unavailable'], 503);
        }
    }

    public function getPruneCandidates(Request $request)
    {
        $hitsThreshold = $request->input('threshold_hits', 5);
        $daysOld = $request->input('days_old', 7); // Default 7 days buffer

        $candidates = KnowledgeBase::select([
            'id',
            'original_text',
            'rephrased_text',
            'keywords',
            'is_template',
            'category',
            'role',
            'model_used',
            'hits',
            'created_at',
            'last_used_at'
        ])
            ->where('hits', '<', $hitsThreshold)
            ->where('created_at', '<', now()->subDays($daysOld))
            ->get();

        return response()->json($candidates);
    }

    public function keepEntry(Request $request)
    {
        $request->validate(['id' => 'required|exists:knowledge_bases,id']);

        $entry = KnowledgeBase::find($request->id);
        // "Reset" the entry so it survives the next prune cycle
        // Set hits to a safe number (e.g. 5) or update last_used_at
        // Let's bump hits to 5 (or whatever the default threshold usually is + 1) to be safe
        $entry->hits = 10;
        $entry->last_used_at = now();
        $entry->save();

        return response()->json(['status' => 'success']);
    }

    public function cleanupKb(Request $request)
    {
        // Now accepts explicit IDs
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:knowledge_bases,id'
        ]);

        try {
            KnowledgeBase::destroy($validated['ids']);

            // Trigger rebuild in AI service to sync index
            $this->notifyAiRebuild();

            return response()->json(['status' => 'success', 'deleted' => count($validated['ids'])]);
        } catch (\Exception $e) {
            Log::error("Failed to cleanup KB: " . $e->getMessage());
            return response()->json(['error' => 'Cleanup Failed'], 500);
        }
    }

    private function formatModelName($name)
    {
        if (!$name)
            return 'Unknown Model';
        return str_replace([':latest', ':8b-instruct-q3_K_M', '-instruct-q3_K_M'], '', $name);
    }
}
