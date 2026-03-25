<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use App\Services\ChatService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;

class ChatController extends Controller
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    #[Post('/api/chat')]
    public function ask(ChatRequest $request, ChatService $service): JsonResponse
    {
        [$result, $sessionKey] = $service->process(request: $request->validated());

        return response()->json([
            'session_key' => $sessionKey,
            'answer' => $result['answer'],
            'sources' => $result['sources'],
            'used_query' => $result['used_query'],
        ]);
    }
}
