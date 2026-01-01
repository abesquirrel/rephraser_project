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
            // Handle CSV Import
            $path = $request->file('file')->getRealPath();
            $data = array_map('str_getcsv', file($path));
            $header = array_shift($data); // Assuming header exists? Check logic. 
            // Actually original app expects no header or specific columns? 
            // Original app used pandas: pd.read_csv(file, header=None, names=['original', 'rephrased'])
            
            // Let's assume standard CSV for now.
            foreach ($data as $row) {
                if (count($row) >= 2) {
                    KnowledgeBase::create([
                        'original_text' => $row[0],
                        'rephrased_text' => $row[1]
                    ]);
                }
            }
        } elseif ($request->has('original_text')) {
             KnowledgeBase::create($request->all());
        }

        // Notify AI
        try {
            Http::post("{$this->aiServiceUrl}/trigger_rebuild");
        } catch (\Exception $e) {
             Log::error("Failed to notify AI service: " . $e->getMessage());
        }

        return response()->json(['status' => 'success']);
    }
}
