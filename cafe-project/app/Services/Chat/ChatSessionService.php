<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use Illuminate\Support\Str;

class ChatSessionService
{
    public const COOKIE_NAME            = 'chat_session';
    public const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30; // 30 ngày

    /**
     * Trả về [ChatConversation, bool $isNew]
     */
    public function resolve(?int $userId, ?string $sessionToken): array
    {
        return $userId
            ? $this->resolveForUser($userId)
            : $this->resolveForGuest($sessionToken);
    }

    private function resolveForUser(int $userId): array
    {
        $conversation = ChatConversation::where('user_id', $userId)
            ->latest('last_activity_at')
            ->first();

        if ($conversation) {
            return [$conversation, false];
        }

        return [
            ChatConversation::create([
                'user_id'          => $userId,
                'last_activity_at' => now(),
            ]),
            true,
        ];
    }

    private function resolveForGuest(?string $sessionToken): array
    {
        if ($sessionToken) {
            $conversation = ChatConversation::whereNull('user_id')
                ->where('session_token', $sessionToken)
                ->first();

            if ($conversation) {
                return [$conversation, false];
            }
        }

        // Tạo mới — token sẽ được set vào cookie ở controller
        $token = (string) Str::uuid();

        return [
            ChatConversation::create([
                'session_token'    => $token,
                'last_activity_at' => now(),
            ]),
            true,
        ];
    }
}