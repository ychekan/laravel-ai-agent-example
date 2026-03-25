<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class QueryExpansionService
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
    public function expand(string $question): array
    {
        $prompt = PromptHelperService::load('query_expansion', ['question' => $question]);

        return $this->ollama->generateJson(prompt: $prompt);
    }
}
