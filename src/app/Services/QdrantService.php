<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class QdrantService
{
    public function __construct(
        protected string     $baseUrl = '',
        protected string     $collection = '',
        private readonly int $timeout = 180,
    )
    {
        $this->baseUrl = rtrim(config(key: 'services.qdrant.base_url'), '/');
        $this->collection = config('services.qdrant.collection');
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function search(array $vector, int $limit = 5): array
    {
        $response = Http::timeout(seconds: $this->timeout)
            ->post(url: $this->baseUrl . "/collections/{$this->collection}/points/search", data: [
                'vector' => $vector,
                'limit' => $limit,
                'with_payload' => true,
            ])
            ->throw()
            ->json();

        return $response['result'] ?? [];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function upsertBatch(array $points): void
    {
        Http::timeout(seconds: $this->timeout)
            ->put(url: $this->baseUrl . "/collections/{$this->collection}/points", data: [
                'points' => $points,
            ])
            ->throw();
    }
}
