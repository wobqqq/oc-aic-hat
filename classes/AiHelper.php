<?php

declare(strict_types=1);

namespace Bsd\AiChat\Classes;

class AiHelper
{
    public static function toEventMessage(array $data): string
    {
        return sprintf("data: %s\n\n", json_encode($data));
    }

    public static function toEventList(string $line): array
    {
        $events = explode("\r\n\r\n", $line);
        $events = array_map(function ($event) {
            $event = str_replace('data: ', '', $event);
            return $event;
        }, $events);

        return $events;
    }

    public static function isHtml(string $value): bool
    {
        return (bool)preg_match("/(<[a-z][\s\S]*>)|(<\/[a-z][\s\S]*>)|<[a-z][\s\S]*\/>/i", $value);
    }

    public static function filterAiEvent(string $value): string
    {
        $value = str_replace('data: ', '', $value);
        $value = str_replace("\r\n\r\n", '', $value);

        return $value;
    }
}
