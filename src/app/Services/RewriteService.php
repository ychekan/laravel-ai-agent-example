<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class RewriteService
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
    public function rewrite(string $question, array $chunks): string
    {
        $context = collect(value: $chunks)
            ->pluck(value: 'text')
            ->implode(value: "\n\n---\n\n");

        $prompt = PromptHelperService::load('rewrite_question', ['question' => $question, 'context' => $context]);

        $json = $this->ollama->generateJson(prompt: $prompt);

        return trim($json['query'] ?? $question);
    }
}
