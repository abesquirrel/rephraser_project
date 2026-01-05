<?php

use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can rephrase text via streaming', function () {
    Http::fake([
        'http://rephraser-ai:5001/rephrase' => Http::response('{"data": "rephrased text"}', 200)
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => 'original text',
        'signature' => 'Paul'
    ]);

    $response->assertStatus(200);
    // Since it's a stream, we might need more complex verification if we were strictly checking content,
    // but assertStatus(200) verifies the controller logic and proxying.
});

it('can approve and learn from a rephrased response', function () {
    Http::fake([
        'http://rephraser-ai:5001/trigger_rebuild' => Http::response(['status' => 'success'], 200)
    ]);

    $data = [
        'original_text' => 'original note',
        'rephrased_text' => 'professional response',
        'category' => 'Technical',
        'model_used' => 'llama3'
    ];

    $response = $this->postJson('/api/approve', $data);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('knowledge_bases', [
        'original_text' => 'original note',
        'rephrased_text' => 'professional response'
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'original_content' => 'original note',
        'action' => 'Approve/Create'
    ]);
});

it('sanitizes input before sending to AI', function () {
    Http::fake([
        'http://rephraser-ai:5001/rephrase' => function ($request) {
            if (!str_contains($request['text'], '<script>') && str_contains($request['text'], 'test')) {
                return Http::response(['data' => 'ok'], 200);
            }
            return Http::response(['error' => 'failed'], 500);
        }
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => '<script>alert("xss")</script>test',
    ]);

    $response->assertStatus(200);
});

it('passes through tuning parameters to AI service', function () {
    Http::fake([
        'http://rephraser-ai:5001/rephrase' => function ($request) {
            if ($request['temperature'] == 0.8 &&
                $request['max_tokens'] == 1200 &&
                $request['kb_count'] == 7) {
                return Http::response(['data' => 'ok'], 200);
            }
            return Http::response(['error' => 'invalid params'], 400);
        }
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => 'test text',
        'temperature' => 0.8,
        'max_tokens' => 1200,
        'kb_count' => 7
    ]);

    $response->assertStatus(200);
});
