<?php

declare(strict_types=1);

namespace Bsd\AiChat\Classes;

use Cache;
use Cookie;
use Ramsey\Uuid\Uuid;

class ChatStorage
{
    private const int TTL = 7200;

    private string $chatStorageKey;

    private string $steamingMessageKey;

    public function __construct()
    {
        $this->steamingMessageKey = Uuid::uuid4()->toString();
        $this->chatStorageKey = Cookie::get('chat_storage_key', '');
    }

    public function initStorage(): void
    {
        $this->chatStorageKey = Cookie::get('chat_storage_key', '');

        if (!empty($this->chatStorageKey)) {
            return;
        }

        $this->chatStorageKey = sprintf('chat_storage_key_%s', Uuid::uuid4()->toString());

         Cookie::queue('chat_storage_key', $this->chatStorageKey);
    }

    public function get(string $key): array
    {
        return Cache::get(sprintf('%s.%s', $this->chatStorageKey, $key), []);
    }

    public function put(string $key, $data): void
    {
        Cache::put(sprintf('%s.%s', $this->chatStorageKey, $key), $data, self::TTL);
    }

    public function forget(string $key): void
    {
        Cache::forget(sprintf('%s.%s', $this->chatStorageKey, $key));
    }

    public function putChatStreaming(string $data): void
    {
        $messages = $this->get('bot_messages');
        $message = end($messages);

        if (empty($message) || $message['key'] !== $this->steamingMessageKey) {
            $messages[] = [
                'key' => $this->steamingMessageKey,
                'role' => 'assistant',
                'content' => $data,
            ];
        } else {
            $message['content'] .= $data;
            $key = count($messages) - 1;
            $messages[$key] = $message;
        }

        $this->put('bot_messages', $messages);
    }
}
