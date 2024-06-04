<?php namespace Bsd\AiChat\Components;

use Bsd\AiChat\Classes\AzureService;
use Bsd\AiChat\Classes\BackService;
use Cms\Classes\CodeBase;
use Cms\Classes\ComponentBase;
use Log;

/**
 *
 */
class AiChat extends ComponentBase
{
    /**
     * @var BackService
     */
    private $bService = null;

    /**
     * @param CodeBase|null $cmsObject
     * @param $properties
     * @param BackService $bService
     */
    public function __construct(CodeBase $cmsObject = null, $properties = [], BackService $bService)
    {
        parent::__construct($cmsObject, $properties);
        $this->bService = $bService;
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
            session()->remove('chat');
            session()->remove('message_type');
        }

        session()->remove('cache_chat');
        session()->remove('message_type');
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|string|void|array
     */
    public function onMessage()
    {
        $messageType = session('message_type') ?? 'normal';
        if (request()->isMethod('post')) {
            if (session()->token() == input('_token')) {
                $data = post();

                if (!isset($data['msg']) || check_if_corrupt($data['msg'])) {
                    Log::debug('Corrupted input');
                    return '';
                }
                $data['msg'] = substr(trim($data['msg']), 0, 300);
                $chat = session('chat') ?? [];
                
                $chat[] = [
                    'role' => 'user',
                    'content' => $data['msg'],
                ];
                $answerObj = ['role' => 'assistant'];

                if (!($answer = $this->bService->reply($chat, $messageType))) {
                    $this->page['maintenance'] = true;
                    return ['#convWrapper' => $this->renderPartial('AiChat::maintenance')];
                }

                if(isset($answer->html)) {
                    $answerObj['content'] = rtrim($answer->html,'.'); // adaugat temporar pana se rezolva la nivel de AI back
                    
                    $chat[] = $answerObj;
                    session()->put('chat', $chat);
                    session()->put('cache_chat', true);
                    session()->put('message_type', $answer->message_type);
                }

                if (request()->segment(1) != 'ai-search') {
                    return redirect('/ai-search/');
                }

                if ((count($chat) + 1) / 2 >= 16) { # limit to 16 questions answered
                    return response()->json([$answerObj, [
                        'content' => trans('bsd.aichat::lang.msg.limit', [], 'ro')
                    ]]);
                } else {
                    if (isset($answerObj['content'])) {
                        $answerObj['content'] = preg_replace('/<a(.*?)href=["\'](.*?)["\'](.*?)>/', '<a$1href="$2" target="_blank"$3>', $answerObj['content']);
                    }
                    return response()->json([$answerObj]);
                }
            }
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|string|void
     */
    public function onValidateMessage()
    {
        if (request()->isMethod('post')) {
            if (session()->token() == input('_token')) {
                $data = post();

                if (!isset($data['msg']) || check_if_corrupt($data['msg'])) {
                    Log::debug('Corrupted input');
                    return '';
                }
                $data['msg'] = substr(trim($data['msg']), 0, 300);

                if (!$this->bService::allow('msg-form:'.request()->ip())) {
                    return '';
                }

                return response()->json($data);
            }
        }
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
     */
    public function chat()
    {
        return session('chat');
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
