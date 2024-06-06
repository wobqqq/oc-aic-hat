<?php

declare(strict_types=1);

namespace Bsd\AiChat\Http\Controllers;

use Bsd\AiChat\Classes\AiHelper;
use Bsd\AiChat\Classes\BackService;
use Bsd\AiChat\Classes\ChatStorage;
use Bsd\AiChat\Exceptions\ChatStreamingException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use System\Classes\PluginManager;
use Throwable;
use Twig;

final class AIController extends Controller
{
    /**
     * @throws Exception
     */
    public function chatStreaming(
        Request $request,
        BackService $backService,
        ChatStorage $chatStorage
    ): StreamedResponse {
        $chatStorage->initStorage();

        $message = $request->string('message')->value();
        $token = $request->session()->token();

        $response = new StreamedResponse(function () use ($backService, $message, $token) {
            try {
                $backService->chatStreaming($message, $token);
            } catch (Exception|Throwable $e) {
                if ($e instanceof ChatStreamingException) {
                    $message = $e->getMessage();
                } else {
                    $partialPath = sprintf(
                        '%s/components/aichat/maintenance.htm',
                        PluginManager::instance()->getPluginPath('Bsd.AiChat')
                    );
                    $message = file_exists($partialPath)
                        ? Twig::parse(file_get_contents($partialPath))
                        : trans('bsd.aichat::lang.msg.common_error', [], 'ro');
                }

                echo AiHelper::toEventMessage(['content' => $message]);

                @ob_flush();
                flush();

                Log::error('ChatStreamingException', (array)$e);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
