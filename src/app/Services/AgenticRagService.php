<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChatSessionRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class AgenticRagService
{
    private int $textLimit = 0;
    private int $maxAttempt = 0;

    public function __construct(
        protected RouterService         $router,
        protected RetrieverService      $retriever,
        protected RewriteService        $rewrite,
        protected AnswerService         $answer,
        protected QueryExpansionService $expander,
        protected ChatSessionRepository $repository,
    )
    {
        $this->textLimit = intval(config(key: 'services.agentic_rag.text_limit'));
        $this->maxAttempt = intval(config(key: 'services.agentic_rag.max_attempt'));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function ask(string $question, string $sessionKey): array
    {
        $session = $this->repository->firstBySessionKey(sessionKey: $sessionKey);

        if (!$this->router->shouldRetrieve(question: $question)) {
            $directAnswer = $this->answer->directAnswer(question: $question);

            $session->update(attributes: [
                'last_question' => $question,
                'rewritten_question' => null,
                'last_answer' => $directAnswer,
                'sources' => [],
            ]);

            return [
                'answer' => $directAnswer,
                'sources' => [],
                'used_query' => $question,
            ];
        }

        $query = $question;
        for ($attempt = 0; $attempt < $this->maxAttempt; $attempt++) {
            // 1. Generate multiple queries using expansion
            $queries = collect($this->expander->expand(question: $query))
                ->filter()->unique()->take(limit: 3)
                ->values()->all();
            $queries[] = $query;
            $queries[] = $query;

            // 2. Search for each query and gather chunks
            $chunks = $this->retriever->searchMultiple(queries: $queries);
            if (empty($chunks)) {
                break;
            }

            // 3. Filter, deduplicate, and sort chunks by score
            $chunks = collect($chunks)->map(function ($c) {
                return [
                    'id' => $c['id'] ?? null,
                    'score' => $c['score'] ?? 0,
                    'document_id' => $c['payload']['document_id'] ?? null,
                    'document_title' => $c['payload']['document_title'] ?? null,
                    'chunk_index' => $c['payload']['chunk_index'] ?? null,
                    'text' => $c['payload']['text'] ?? '',
                ];
            });

            $chunks = $chunks
                ->filter(fn($c) => isset($c['text'], $c['document_id'], $c['chunk_index']) &&
                    mb_strlen($c['text']) < $this->textLimit
                )
                ->groupBy(fn($c) => $c['document_id'] . '_' . $c['chunk_index'])
                ->map(fn($group) => collect($group)
                    ->sortByDesc(fn($c) => $c['score'] ?? 0)
                    ->first()
                )
                ->filter()
                ->sortByDesc(fn($c) => $c['score'] ?? 0)
                ->take(5)
                ->values()
                ->all();

            // 4. Reorder chunks to prioritize diversity of sources
            $order = [0, 2, 1, 3, 4];
            $chunksCollection = collect(value: $chunks)->values();
            $chunks = collect(value: $order)
                ->filter(callback: fn ($i) => $chunksCollection->has($i))
                ->map(callback: fn ($i) => $chunksCollection[$i] ?? null)
                ->values()
                ->all();

            // 5. Check relevance and decide whether to answer or rewrite
            if (!empty($chunks)) {
                $finalAnswer = $this->answer->answerWithContext(question: $question, chunks: $chunks);

                $sources = collect(value: $chunks)->map(callback: function (array $chunk) {
                    return [
                        'document_id' => $chunk['document_id'],
                        'document_title' => $chunk['document_title'],
                        'chunk_index' => $chunk['chunk_index'],
                    ];
                })->unique(key: fn($c) => $c['document_id'] . '_' . $c['chunk_index'])->values()->all();

                $session->update(attributes: [
                    'last_question' => $question,
                    'rewritten_question' => $query !== $question ? $query : null,
                    'last_answer' => $finalAnswer,
                    'sources' => $sources,
                ]);

                return [
                    'answer' => $finalAnswer,
                    'sources' => $sources,
                    'used_query' => $query,
                ];
            }

            // 6. If not relevant, rewrite the query and try again
            $newQuery = $this->rewrite->rewrite(question: $query, chunks: $chunks);
            if ($newQuery === $query) {
                break;
            }
            $query = $newQuery;
        }

        // 7. If all attempts fail, return fallback answer
        $fallback = $this->answer->fallback(question: $question);

        $session->update(attributes: [
            'last_question' => $question,
            'rewritten_question' => $query !== $question ? $query : null,
            'last_answer' => $fallback,
            'sources' => [],
        ]);

        return [
            'answer' => $fallback,
            'sources' => [],
            'used_query' => $query,
        ];
    }
}
