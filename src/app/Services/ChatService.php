<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(
        protected AgenticRagService $rag,
    )
    {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function process(array $request): array
    {
        $sessionKey = $request['session_key'] ?? (string)Str::uuid();

        $result = $this->rag->ask(
            question: trim($request['question']),
            sessionKey: $sessionKey,
        );

        return [$result, $sessionKey];
    }
}
