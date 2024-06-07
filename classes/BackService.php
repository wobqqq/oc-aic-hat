<?php

namespace Bsd\AiChat\Classes;

use Bsd\AiChat\Exceptions\ChatStreamingException;
use Config;
use Exception;
use Session;

readonly class BackService
{
    private int $limitOfQuestions;

    public function __construct(private AiService $aiService, private ChatStorage $chatStorage)
    {
        $this->limitOfQuestions = Config::get('app.ai.limit_of_questions', 16);
    }

    /**
     * @param $key
     * @return bool
     */
    public static function allow($key): bool
    {
        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        if ($limiter->tooManyAttempts($key, 5)) {
            return false;
        }

        $limiter->hit($key, 60);

        return true;
    }

    /**
     * @throws ChatStreamingException
     * @throws Exception
     */
    public function chatStreaming(string $message, string $token): void
    {
        if (Session::token() !== $token) {
            throw new Exception('CSRF token error');
        }

        if (empty($message) || check_if_corrupt($message)) {
            throw new ChatStreamingException(trans('bsd.aichat::lang.msg.incorrect_message', [], 'ro'));
        }

        $message = trim($message);
        $message = substr($message, 0, 300);

        if (!self::allow('msg-form:' . request()->ip())) {
            throw new ChatStreamingException(trans('bsd.aichat::lang.msg.ip_restriction', [], 'ro'));
        }

        $messages = $this->chatStorage->get('bot_messages', []);

        $botMessages = array_filter($messages, function ($message) {
            return $message['role'] === 'assistant';
        });

        if (count($botMessages) >= $this->limitOfQuestions) {
            throw new ChatStreamingException(trans('bsd.aichat::lang.msg.limit', [], 'ro'));
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $this->aiService->chatStreaming($messages);
    }
}
