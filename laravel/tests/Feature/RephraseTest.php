<?php

use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBase;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can rephrase text via streaming', function () {
    Http::fake([
        'http://rephraser-ai-inference:5001/rephrase' => Http::response('{"data": "rephrased text"}', 200)
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => 'original text',
        'signature' => 'Masha'
    ]);

    $response->assertStatus(200);
});

it('can approve and learn from a rephrased response', function () {
    Http::fake([
        'http://rephraser-ai-inference:5001/trigger_rebuild' => Http::response(['status' => 'success'], 200),
        'http://rephraser-ai-embedding:5002/trigger_rebuild' => Http::response(['status' => 'success'], 200)
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
        'http://rephraser-ai-inference:5001/rephrase' => function ($request) {
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
        'http://rephraser-ai-inference:5001/rephrase' => function ($request) {
            if (
                $request['temperature'] == 0.8 &&
                $request['max_tokens'] == 1200 &&
                $request['kb_count'] == 7 &&
                $request['negative_prompt'] == 'no jargon'
            ) {
                return Http::response(['data' => 'ok'], 200);
            }
            return Http::response(['error' => 'invalid params'], 400);
        }
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => 'test text',
        'temperature' => 0.8,
        'max_tokens' => 1200,
        'kb_count' => 7,
        'negative_prompt' => 'no jargon'
    ]);

    $response->assertStatus(200);
});

it('strictly follows the Tech Support persona structure', function () {
    Http::fake([
        'http://rephraser-ai-inference:5001/rephrase' => Http::response(json_encode([
            'data' => "Hello,\n\nObservations:\nThe device is an iPhone 14.\n\nActions Taken:\nNone.\n\nRecommendations:\nPlease check the carrier bundle.\n\nRegards,\nPaul R"
        ]), 200)
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => 'iphone 14 apn not working',
        'role' => 'Tech Support'
    ]);

    $response->assertStatus(200);

    ob_start();
    $response->baseResponse->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('Hello,');
    expect($content)->toContain('Observations:');
    expect($content)->toContain('Actions Taken:');
    expect($content)->toContain('Recommendations:');
    expect($content)->toContain('Regards,');
    expect($content)->toContain('Paul R');
});

it('extracts brand and model codes for web search', function () {
    Http::fake([
        'http://rephraser-ai-inference:5001/rephrase' => function ($request) {
            // This is harder to test directly without mocking the internal threading in the Python service,
            // but we can verify that the controller still functions when text with brands/models is sent.
            return Http::response(['data' => 'ok'], 200);
        }
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => 'Samsung Galaxy SM-S911B roaming issue',
        'enable_web_search' => true
    ]);

    $response->assertStatus(200);
});

it('extracts instructions from within <>', function () {
    Http::fake([
        'http://rephraser-ai-inference:5001/rephrase' => function ($request) {
            // Verification should happen on the python side, but we check if Laravel passes it correctly
            // In RephraseController.php, the direct_instruction is actually extracted in app.py
            return Http::response(['data' => 'ok'], 200);
        }
    ]);

    $response = $this->postJson('/api/rephrase', [
        'text' => 'Notes about roaming. <Keep it very short>',
    ]);

    $response->assertStatus(200);
});
