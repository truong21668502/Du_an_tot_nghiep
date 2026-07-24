<?php

namespace App\Services\Chat;

use App\Exceptions\AiUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatAiService
{
    private array $models;

    public function __construct()
    {
        // Lọc bỏ model null/empty trong trường hợp env chưa set đủ
        $this->models = array_values(array_filter([
            config('ai.models.primary'),
            config('ai.models.secondary'),
            config('ai.models.tertiary'),
        ]));
    }

    /**
     * Gọi AI với tool calling support, fallback qua 3 model nếu lỗi.
     * Trả về raw message object từ API (có thể chứa tool_calls hoặc content).
     *
     * @throws AiUnavailableException khi tất cả model đều lỗi
     */
    public function call(array $messages, array $tools = []): array
    {
        $lastException = null;

        foreach ($this->models as $model) {
            try {
                return $this->callModel($model, $messages, $tools);
            } catch (\Throwable $e) {
                Log::warning("ChatAI [{$model}] failed", ['error' => $e->getMessage()]);
                $lastException = $e;
            }
        }

        throw new AiUnavailableException(
            'Hệ thống AI đang tạm thời gián đoạn, vui lòng thử lại sau vài phút.',
            0,
            $lastException
        );
    }

    private function callModel(string $model, array $messages, array $tools): array
    {
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => config('ai.max_tokens', 1024),
            'temperature' => config('ai.temperature', 0.7),
        ];

        if (!empty($tools)) {
            $payload['tools']       = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('ai.ai_key'),
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => config('app.name', 'Nắng coffee'),
        ])
            ->timeout(45)
            ->post(config('ai.ai_url'), $payload);

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}: " . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error'])) {
            throw new \RuntimeException($data['error']['message'] ?? 'API error');
        }

        if (empty($data['choices'][0]['message'])) {
            throw new \RuntimeException('Response rỗng từ OpenRouter');
        }

        return $data['choices'][0]['message'];
    }
}