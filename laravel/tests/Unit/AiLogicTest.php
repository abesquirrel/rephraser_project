<?php

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\RephraseController;
use Illuminate\Http\Request;

uses(Tests\TestCase::class);

it('includes the secure API key in AI service calls', function () {
    config(['rephraser.ai_key' => 'test_key_123']);

    Http::fake([
        'http://rephraser-ai:5001/suggest_keywords' => function ($request) {
            if ($request->hasHeader('X-AI-KEY', 'test_key_123')) {
                return Http::response(['keywords' => 'test'], 200);
            }
            return Http::response(['error' => 'unauthorized'], 401);
        }
    ]);

    $controller = new RephraseController();
    $request = new Request(['text' => 'test text']);

    $response = $controller->suggestKeywords($request);

    expect($response)->toBeArray();
    expect($response['keywords'])->toBe('test');
});

it('handles AI service failure gracefully', function () {
    Http::fake([
        'http://rephraser-ai:5001/suggest_keywords' => Http::response(['error' => 'fail'], 500)
    ]);

    $controller = new RephraseController();
    $request = new Request(['text' => 'test text']);

    $response = $controller->suggestKeywords($request);
    
    expect($response)->toBeArray();
    expect($response['error'])->toBe('fail');
});
