<?php namespace Bsd\AiChat;

use Bsd\AiChat\Classes\AzureService;
use Orhanerday\OpenAi\OpenAi;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return ['\Bsd\AiChat\Components\AiChat' => 'AiChat'];
    }

    public function boot()
    {
//        if (request()->segment(1) == 'ai-search') {
//            $open_ai = new OpenAi(config('bsd.aichat::ai.open_ai'));
//            $open_ai->setBaseURL('http://37.251.255.8:8000');
////            $open_ai->setProxy("http://37.251.255.8:8000");
//            dump($open_ai);
//            dump($open_ai->getCURLInfo());
//        $open_ai->setHeader(["Connection"=>"keep-alive"]);
//            dump($open_ai->chat(['message' => 'salut']));
//        }
//        if (app()->runningInBackend()) {
//            (new AzureService())->getToken();
//            (new AzureService('Recording.wav'))->sendFile();
//        }
    }
}
