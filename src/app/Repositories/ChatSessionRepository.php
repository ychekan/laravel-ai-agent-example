<?php

namespace App\Repositories;

use App\Models\ChatSession;

class ChatSessionRepository
{
    public function firstBySessionKey(string $sessionKey)
    {
        return ChatSession::firstOrCreate(attributes: ['session_key' => $sessionKey]);
    }
}
