<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class RetrieverService
{
    public function __construct(
        protected OllamaService $ollama,
        protected QdrantService $qdrant,
    )
    {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function searchDocs(string $query, int $limit = 5): array
    {
        $embedding = $this->ollama->embed(text: $query);

        $results = $this->qdrant->search(vector: $embedding, limit: $limit);

        return collect(value: $results)->map(callback: function (array $item) {
            return [
                'score' => $item['score'] ?? null,
                'document_id' => $item['payload']['document_id'] ?? null,
                'document_title' => $item['payload']['document_title'] ?? null,
                'chunk_index' => $item['payload']['chunk_index'] ?? null,
                'text' => $item['payload']['text'] ?? '',
            ];
        })->all();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function searchMultiple(array $queries): array
    {
        $all = [];

        foreach ($queries as $query) {
            $embedding = $this->ollama->embed(text: $query);

            $results = $this->qdrant->search(vector: $embedding, limit: 5);

            $all = array_merge($all, $results);
        }

        // remove duplicates
        return collect(value: $all)
            ->unique(key: fn($item) => $item['id'] ?? null)
            ->values()
            ->all();
    }
}
