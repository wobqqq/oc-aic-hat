<?php

declare(strict_types=1);

namespace Bsd\AiChat\Classes;

use Config;

class AiService
{
    private string $rootUrl;

    public function __construct(private readonly ChatStorage $chatStorage)
    {
        $this->rootUrl = Config::get('app.ai.root_url', 'http://37.251.255.8:8000');
    }

    public function chatStreaming(array $messages, string $conversationState = 'normal'): void
    {
        $url = sprintf('%s/chat/streaming', $this->rootUrl);

        $requestData = [
            'messages' => $messages,
            'conversation_state' => $conversationState,
        ];

        $fullBotMessage = '';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/event-stream',
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$fullBotMessage) {
            return $this->serveEventData($data, $fullBotMessage);
        });

        ini_set('implicit_flush', 1);
        ob_implicit_flush(true);

        curl_exec($ch);
        curl_close($ch);

        $fullBotMessage = AiHelper::filterAiEvent($fullBotMessage);

        $this->chatStorage->putBotMessage($fullBotMessage);
    }

    private function serveEventData(string $data, &$fullMessage): int
    {
        if (starts_with($data, ": ping",) && ends_with($data, "\r\n\r\n")) {
            echo AiHelper::toEventMessage(['content' => '']);
            @ob_flush();
            flush();
        } else {
            $fullMessage .= $data;
            $events = AiHelper::toEventList($data);

            foreach ($events as $event) {
                echo AiHelper::toEventMessage([
                    'content' => $event,
                    'is_html' => AiHelper::isHtml($event),
                ]);
                @ob_flush();
                flush();
            }
        }

        return strlen($data);
    }
}
