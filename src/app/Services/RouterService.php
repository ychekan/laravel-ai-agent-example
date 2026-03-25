<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class RouterService
{
    public function __construct(
        protected OllamaService $ollama
    )
    {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function shouldRetrieve(string $question): bool
    {
        $prompt = PromptHelperService::load('should_retrieve', ['question' => $question]);

        $json = $this->ollama->generateJson(prompt: $prompt);

        return (bool)($json['retrieve'] ?? true);
    }
}
