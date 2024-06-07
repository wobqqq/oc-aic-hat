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
        $events = explode('data: ', $line);
        $events = array_map(function (string $event) {
            return rtrim($event, "\r\n\r\n");
        }, $events);

        return $events;
    }

    public static function isHtml(string $value): bool
    {
        return (bool)preg_match("/(<[a-z][\s\S]*>)|(<\/[a-z][\s\S]*>)|<[a-z][\s\S]*\/>/i", $value);
    }
}
