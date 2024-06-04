<?php namespace Bsd\AiChat\Classes;

/**
 *
 */
class BackService
{
    /**
     * @param array $chat
     * @param array $messageType
     * @return mixed|null
     */
    public function reply(array $chat, $messageType)
    {
        $options = get_curl_options_std(
            "http://37.251.255.17:8000/chat",
            'post',
            json_encode(array_merge(['messages' => $chat], ['conversation_state' => $messageType]))
        );
        $result = curl_std_call($options);

        if($result === null){
            return null;
        }

        if (isset($result['response']->errors) || $result['http_code'] != 200) {
            \Log::debug("User reply fail");
            return null;
        }

        return $result['response'];
    }

    /**
     * @param $key
     * @return bool
     */
    public static function allow($key)
    {
        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        if ($limiter->tooManyAttempts($key, 5)) {
            return false;
        }

        $limiter->hit($key, 60);

        return true;
    }
}
