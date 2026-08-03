<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use App\Models\AiResponseLog;

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
        $data['custom_search_sources'] = $this->sanitize($data['custom_search_sources'] ?? '');

        // Normalize web search keys for both logging and AI service consistency
        $webSearch = $data['web_search_enabled'] ?? $data['enable_web_search'] ?? false;
        if (is_string($webSearch)) {
            $webSearch = ($webSearch === 'true' || $webSearch === '1');
        }
        $data['web_search_enabled'] = $webSearch;
        $data['enable_web_search'] = $webSearch;

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
        $generationLog = AiResponseLog::create([
            'session_id' => $sessionId,
            'model_id' => $data['model'] ?? 'unknown',
            'model_display_name' => $this->formatModelName($data['model'] ?? 'unknown'),
            'original_text' => $data['text'],
            'input_text_length' => $inputLength,
            'temperature' => $data['temperature'] ?? null,
            'max_tokens' => $data['max_tokens'] ?? null,
            'kb_count' => $data['kb_count'] ?? null,
            'web_search_enabled' => $data['web_search_enabled'],
            'template_mode' => $data['template_mode'] ?? false,
            'category' => $data['category'] ?? null,
            'prompt_tokens' => (int) ($inputLength / 3), // Approx 3-4 chars per token
        ]);

        $startTime = microtime(true);

        // Stream response from Python service
        try {
            $response = $this->aiCall()
                ->withOptions(['stream' => true])
                ->post("{$this->inferenceServiceUrl}/rephrase", $data);

        } catch (\Exception $e) {
            $generationLog->update([
                'is_error' => true,
                'error_message' => 'Connection Error: ' . $e->getMessage()
            ]);
            throw $e;
        }

        return response()->stream(function () use ($response, $generationLog, $startTime) {
            try {
                $body = $response->toPsrResponse()->getBody();
                $accumulatedOutput = '';

                while (!$body->eof()) {
                    $chunk = $body->read(1024);
                    if (empty($chunk))
                        continue;

                    echo $chunk;
                    $accumulatedOutput .= $chunk;

                    if (!app()->environment('testing')) {
                        if (ob_get_level() > 0)
                            ob_flush();
                        flush();
                    }
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
                $kbUsageData = $parsedMeta['meta']['kb_usage'] ?? [];
                $kbIds = $parsedMeta['meta']['kb_ids'] ?? []; // Fallback
                $actualTokens = $parsedMeta['meta']['tokens'] ?? 0;
                $promptTokens = $parsedMeta['meta']['prompt_tokens'] ?? 0;
                $outputLength = strlen($finalContent);

                $generationLog->update([
                    'generation_time_ms' => (int) $duration,
                    'rephrased_text' => $accumulatedOutput,
                    'output_text_length' => $outputLength,
                    'prompt_tokens' => $promptTokens ?: $generationLog->prompt_tokens,
                    'completion_tokens' => $actualTokens ?: (int) ($outputLength / 4),
                    'total_tokens' => ($promptTokens && $actualTokens)
                        ? ($promptTokens + $actualTokens)
                        : (($actualTokens ?: (int) ($outputLength / 4)) + ($promptTokens ?: ($generationLog->prompt_tokens ?? 0))),
                    'meta_data' => [
                        'kb_usage' => !empty($kbUsageData) ? $kbUsageData : $kbIds
                    ]
                ]);

                // Emit generation_id back to client explicitly
                echo json_encode(['generation_log_id' => $generationLog->id]) . "\n";
                if (ob_get_level() > 0)
                    ob_flush();
                flush();

            } catch (\Exception $e) {
                Log::error("Stream processing error: " . $e->getMessage());
                // Silently fail to avoid sending HTML error page into the JSON stream
                $generationLog->update([
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
            'presence_penalty' => 'nullable|numeric',
            'generation_id' => 'nullable|integer'
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
        $wasEdited = false;
        if (!empty($validated['original_text']) && !empty($validated['rephrased_text'])) {
            $editDist = levenshtein($validated['original_text'], $validated['rephrased_text']);
            if ($editDist > 0 || (isset($request['isEditing']) && $request['isEditing'])) {
                $wasEdited = true;
            }
        }

        // Try to link back to the AiResponseLog exactly using generation_id (or fallback to latest)
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        $targetGen = null;

        if (!empty($validated['generation_id'])) {
            $targetGen = \App\Models\AiResponseLog::find($validated['generation_id']);
        } elseif ($sessionId) {
            $targetGen = \App\Models\AiResponseLog::where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if ($targetGen) {
            $targetGen->update([
                'knowledge_base_id' => $entry->id,
                'was_approved' => true,
                'was_rejected' => false, // Ensure it's not rejected if being approved
                'was_edited' => $wasEdited,
                'edit_distance' => $editDist
            ]);
        }

        // 2. Notify AI Service to rebuild index
        $this->notifyAiRebuild();

        return response()->json(['status' => 'success', 'id' => $entry->id]);
    }

    public function reject(Request $request)
    {
        $validated = $request->validate([
            'generation_id' => 'required|integer|exists:ai_response_logs,id'
        ]);

        $log = AiResponseLog::find($validated['generation_id']);
        $log->update([
            'was_rejected' => true,
            'was_approved' => false
        ]);

        return response()->json(['status' => 'success']);
    }

    public function suggestKeywords(Request $request)
    {
        $text = $this->sanitize($request->input('text', ''));
        $response = $this->aiCall()->post("{$this->inferenceServiceUrl}/suggest_keywords", ['text' => $text]);
        return $response->json();
    }

    public function getHistory(Request $request)
    {
        $sessionId = $request->query('session_id') ?? $request->header('X-Session-ID');
        if (!$sessionId) {
            return response()->json(['history' => []]);
        }

        $logs = \App\Models\AiResponseLog::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $history = $logs->map(function ($log) {
            return [
                'id' => $log->knowledge_base_id,
                'generation_id' => $log->id,
                'original' => $log->original_text,
                'rephrased' => $log->rephrased_text,
                'keywords' => $log->meta_data['keywords'] ?? '',
                'is_template' => (bool) $log->template_mode,
                'category' => $log->category,
                'approved' => (bool) $log->was_approved,
                'expanded' => false,
                'modelA_name' => $log->model_display_name,
                'isEditing' => false,
                'timestamp' => $log->created_at->toIso8601String(),
                'duration' => $log->generation_time_ms,
                'config' => [
                    'temperature' => $log->temperature,
                    'maxTokens' => $log->max_tokens,
                ]
            ];
        });

        return response()->json(['history' => $history]);
    }

    public function getAuditLogs()
    {
        return AuditLog::orderBy('created_at', 'desc')->take(50)->get();
    }

    public function getModels()
    {
        return response()->json([
            'models' => [
                'open-mistral-nemo',
                'mistral-small-latest',
                'mistral-tiny',
            ]
        ]);
    }

    public function upload_kb(Request $request)
    {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->getRealPath();
            $file = fopen($path, 'r');
            $header = fgetcsv($file);
            $modelUsed = $request->input('model_used', null);

            // Check if first row is a header or data
            $isHeader = false;
            if ($header && (stripos($header[0], 'original') !== false || stripos($header[1], 'rephrased') !== false)) {
                $isHeader = true;
            }

            if (!$isHeader && $header) {
                // If not header, process it as data
                $this->storeKnowledgeEntry($header, $modelUsed);
            }

            while (($row = fgetcsv($file)) !== false) {
                $this->storeKnowledgeEntry($row, $modelUsed);
            }
            fclose($file);
        } elseif ($request->has('original_text')) {
            $validated = $request->validate([
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
            KnowledgeBase::create($validated);

            // Also add to audit log for consistency
            AuditLog::create([
                'action' => 'Manual Add',
                'original_content' => $validated['original_text'],
                'rephrased_content' => $validated['rephrased_text'],
                'model_used' => $validated['model_used'] ?? null,
                'user_name' => 'System'
            ]);
        }

        // Notify AI
        // Notify AI
        $this->notifyAiRebuild();

        return response()->json(['status' => 'success']);
    }

    private function storeKnowledgeEntry($row, $modelUsed = null)
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
                    'category' => $category,
                    'model_used' => $modelUsed
                ]);

                AuditLog::create([
                    'action' => 'Import',
                    'original_content' => $original,
                    'rephrased_content' => $rephrased,
                    'model_used' => $modelUsed,
                    'user_name' => 'System'
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



    // --- KB Export ---

    public function exportKb(Request $request)
    {
        $format = strtolower($request->input('format', 'csv'));

        $entries = KnowledgeBase::select([
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
            'updated_at',
        ])->orderBy('id')->get();

        $timestamp = now()->format('Ymd_His');

        if ($format === 'json') {
            return response()->json($entries)
                ->header('Content-Disposition', "attachment; filename=\"kb_export_{$timestamp}.json\"")
                ->header('Content-Type', 'application/json');
        }

        // Default: CSV
        $filename = "kb_export_{$timestamp}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($entries) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
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
                'updated_at',
            ]);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->id,
                    $entry->original_text,
                    $entry->rephrased_text,
                    $entry->keywords,
                    $entry->is_template ? '1' : '0',
                    $entry->category,
                    $entry->role,
                    $entry->model_used,
                    $entry->hits,
                    $entry->created_at,
                    $entry->updated_at,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }



    private function formatModelName($name)
    {
        if (!$name)
            return 'Unknown Model';
        return str_replace([':latest', ':8b-instruct-q3_K_M', '-instruct-q3_K_M'], '', $name);
    }
}
