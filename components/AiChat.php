<?php namespace Bsd\AiChat\Components;

use Bsd\AiChat\Classes\AzureService;
use Bsd\AiChat\Classes\ChatStorage;
use Cms\Classes\ComponentBase;

/**
 *
 */
class AiChat extends ComponentBase
{
    protected readonly ChatStorage $chatStorage;

    public function __construct($cmsObject = null, $properties = [])
    {
        $this->chatStorage = app(ChatStorage::class);

        parent::__construct($cmsObject, $properties);
    }

    /**
     * @return string[]
     */
    public function componentDetails()
    {
        return [
            'name' => 'Ai Chat',
            'description' => ''
        ];
    }

    /**
     * @return void
     */
    public function onRun()
    {
        $this->addJs([
            'assets/script/ai-chat.js',

            // audio recording
            'assets/script/speech-to-text/recorder.js',
            'assets/script/speech-to-text/main.js'
        ]);
        $this->addCss(['assets/style/ai-chat.scss']);
        $pageWasRefreshed = isset($_SERVER['HTTP_CACHE_CONTROL']);

        if ($pageWasRefreshed || request()->segment(1) != 'ai-search') {
            $this->chatStorage->forget('bot_messages');
        }
    }

    public function onBack()
    {
        return redirect()->to('/');
    }

    public function onRegister()
    {
        $tmp_name = $_FILES["file"]["tmp_name"];
        $name = md5(date('Y-m-d H:i:s')) . ".wav";
        move_uploaded_file($tmp_name, storage_path('app/recordings/' . $name));

        $AzureService = new AzureService($name);
        return $AzureService->execute();
    }
}
