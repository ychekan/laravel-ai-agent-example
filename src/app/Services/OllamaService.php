<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OllamaService
{
    public function __construct(
        protected string     $baseUrl = '',
        protected string     $embedModel = '',
        protected string     $chatModel = '',
        private readonly int $timeout = 180,
    )
    {
        $this->baseUrl = config(key: 'services.ollama.base_url');
        $this->embedModel = config(key: 'services.ollama.embed_model');
        $this->chatModel = config(key: 'services.ollama.chat_model');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function embed(string $text): array
    {
        $response = Http::timeout(seconds: $this->timeout)
            ->post(url: $this->baseUrl . '/api/embeddings', data: [
                'model' => $this->embedModel,
                'prompt' => $text,
            ])
            ->throw()
            ->json();

        return $response['embedding'] ?? [];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function generate(string $prompt): string
    {
        $response = Http::timeout(seconds: $this->timeout)
            ->post(url: $this->baseUrl . '/api/generate', data: [
                'model' => $this->chatModel,
                'prompt' => $prompt,
                'stream' => false,
            ])
            ->throw()
            ->json();

        return trim(string: $response['response'] ?? '');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function generateJson(string $prompt): array
    {
        $raw = $this->generate(prompt: $prompt);
        $decoded = json_decode(json: $raw, associative: true);

        if (!is_array(value: $decoded)) {
            throw new \RuntimeException('LLM did not return valid JSON: ' . $raw);
        }

        return $decoded;
    }
}
