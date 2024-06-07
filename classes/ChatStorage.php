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

    public function __construct()
    {
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

    public function get(string $key, $default = null): array
    {
        return Cache::get(sprintf('%s.%s', $this->chatStorageKey, $key), $default);
    }

    public function put(string $key, $data): void
    {
        Cache::put(sprintf('%s.%s', $this->chatStorageKey, $key), $data, self::TTL);
    }

    public function forget(string $key): void
    {
        Cache::forget(sprintf('%s.%s', $this->chatStorageKey, $key));
    }

    public function putBotMessage(string $data): void
    {
        $messages = $this->get('bot_messages', []);
        $messages[] = [
            'role' => 'assistant',
            'content' => $data,
        ];

        $this->put('bot_messages', $messages);
    }
}
