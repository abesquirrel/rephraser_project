<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Gemini\Contracts\ClientContract;
use Gemini\Contracts\Resources\GenerativeModelContract;
use Gemini\Responses\StreamResponse;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

class GeminiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gemini_route_activation(): void
    {
        // Mock Embedding Service (RAG)
        Http::fake([
            'http://rephraser-ai-embedding:5002/retrieve' => Http::response(['results' => []], 200),
        ]);

        $streamResponse = $this->createMockStreamResponse();

        // Mock Generative Model
        /** @var GenerativeModelContract&\Mockery\MockInterface $mockGenerativeModel */
        $mockGenerativeModel = \Mockery::mock(GenerativeModelContract::class);
        $mockGenerativeModel->shouldReceive('withSystemInstruction')->once()->andReturnSelf();
        $mockGenerativeModel->shouldReceive('streamGenerateContent')->once()->andReturn($streamResponse);

        // Mock Client
        /** @var ClientContract&\Mockery\MockInterface $mockClient */
        $mockClient = \Mockery::mock(ClientContract::class);
        $mockClient->shouldReceive('generativeModel')
            ->with('gemini-2.0-flash')
            ->once()
            ->andReturn($mockGenerativeModel);

        $this->instance(ClientContract::class, $mockClient);

        $response = $this->postJson('/api/rephrase', [
            'text' => 'Test Gemini',
            'model' => 'gemini-2.0-flash',
            'signature' => 'Tester'
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Hello from Gemini', $response->streamedContent());
    }

    private function createMockStreamResponse(): StreamResponse
    {
        $responseData = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [['text' => 'Hello from Gemini']],
                        'role' => 'model'
                    ],
                    'finishReason' => 'STOP',
                    'index' => 0
                ]
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'totalTokenCount' => 20
            ]
        ];

        $stream = Utils::streamFor((string) json_encode($responseData));

        /** @var ResponseInterface&\Mockery\MockInterface $mockPsrResponse */
        $mockPsrResponse = \Mockery::mock(ResponseInterface::class);
        $mockPsrResponse->shouldReceive('getBody')->andReturn($stream);

        return new StreamResponse(GenerateContentResponse::class, $mockPsrResponse);
    }

    public function test_gemini_rate_limit_handling(): void
    {
        /** @var ClientContract&\Mockery\MockInterface $mockClient */
        $mockClient = \Mockery::mock(ClientContract::class);
        $mockClient->shouldReceive('generativeModel')
            ->andThrow(new \Exception('429 Quota exceeded'));

        $this->instance(ClientContract::class, $mockClient);

        $response = $this->postJson('/api/rephrase', [
            'text' => 'Test Rate Limit',
            'model' => 'gemini-2.0-flash',
            'signature' => 'Tester'
        ]);

        $response->assertStatus(429);
        $this->assertStringContainsString('Rate Limit Exceeded', $response->streamedContent());
    }
}
