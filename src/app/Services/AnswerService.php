<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class AnswerService
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
    public function answerWithContext(string $question, array $chunks): string
    {
        $context = collect($chunks)
            ->map(callback: function (array $chunk) {
                $title = $chunk['document_title'] ?? 'Unknown';
                $index = $chunk['chunk_index'] ?? '?';
                $text = $chunk['text'] ?? '';

                return "[Source: {$title}, chunk {$index}]\n{$text}";
            })
            ->implode(value: "\n\n---\n\n");

        $prompt = PromptHelperService::load(name: 'answer_with_context', vars: ['question' => $question, 'context' => $context]);

        return $this->ollama->generate(prompt: $prompt);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function directAnswer(string $question): string
    {
        $prompt = PromptHelperService::load(name: 'direct_answer', vars: ['question' => $question]);

        return $this->ollama->generate(prompt: $prompt);
    }

    public function fallback(string $question): string
    {
        return "I couldn't find a reliable answer in the uploaded documents for: {$question}";
    }
}
