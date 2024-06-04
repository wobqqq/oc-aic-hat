<?php

namespace Bsd\AiChat\Classes;


class AzureService
{
    private const KEY = 'ddef0013a9264bd9b8990b6271eb356e';
    protected $subscriptionKey;
    protected $filename;
    protected $output_format = "detailed";
    protected $language = "ro-RO";
    protected $locale = "ro-RO";
    protected $recognition_mode = "interactive";
    protected $profanity_mode = "raw";

    protected $_fileUrl = 'https://westeurope.stt.speech.microsoft.com/speech/recognition/%recognition_mode%/cognitiveservices/v1?language=%language%&format=%output_format%&profanity=%profanity_mode%'; //?language=%language%&format=%output_format%
    protected $_token;

    /**
     * MicrosoftSpeechAPI constructor.
     * Set default value for variable and get filename from storage/app
     * @param null $filename
     */
    public function __construct($filename = null)
    {
        $this->subscriptionKey = self::KEY;
        $this->filename = $filename;
        $this->_result = null;
    }

    /**
     * Execute query
     * @return null
     */
    public function execute()
    {
        $this->sendFile();

        return $this->_result;
    }

    /**
     * Sends wav the file to the Azure Speech API
     * @url https://docs.microsoft.com/pl-pl/azure/cognitive-services/speech/how-to/how-to-chunked-transfer
     * @return mixed|null
     */
    public function sendFile()
    {
        $file = file_get_contents(storage_path('app/recordings/' . $this->filename));
        $s = curl_init();

        curl_setopt($s, CURLOPT_URL, $this->generateUrl());
        curl_setopt($s, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_POSTFIELDS, $file);
        curl_setopt($s, CURLOPT_HTTPHEADER, [
            'Content-Type: audio/wav; codec=audio/pcm; samplerate=16000',
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey
        ]);

        $result = curl_exec($s);
        curl_close($s);

        if ($result === false) {
            printf("cUrl error (#%d): %s<br>\n", curl_errno($s), htmlspecialchars(curl_error($s)));
        } else {
            $this->_result = $result;
            return $this->_result;
        }
    }

    /**
     * Replace variables from the url to variables stored in the configuration
     * @return string
     */
    public function generateUrl(): string
    {
        $this->_fileUrl = strtr($this->_fileUrl, [
            "%recognition_mode%" => $this->recognition_mode,
            "%language%" => $this->language,
            "%locale%" => $this->locale,
            "%output_format%" => $this->output_format,
            "%profanity_mode%" => $this->profanity_mode,
        ]);

        return $this->_fileUrl;
    }

    /**
     * Gets response
     * @return null
     */
    public function getResponse()
    {
        return $this->_result;
    }

    /**
     * Get Filename
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $value
     */
    public function setFilename(string $value)
    {
        $this->filename = $value;
    }

    /**
     * Get Output Format simple / detailed
     * @url https://docs.microsoft.com/pl-pl/azure/cognitive-services/speech/concepts#output-format
     * @return string
     */
    public function getOutputFormat(): string
    {
        return $this->output_format;
    }

    /**
     * Set Output Format simple / detailed
     * @url https://docs.microsoft.com/pl-pl/azure/cognitive-services/speech/concepts#output-format
     * @param string $value
     */
    public function setOutputFormat(string $value)
    {
        if ($value = 'simple' || $value = 'detailed') {
            $this->output_format = $value;
        } else {
            $this->output_format = 'simple';
        }

    }

    /**
     * protected $recognition_mode = "interactive";
     */

    /**
     * Get language
     * @url https://docs.microsoft.com/pl-pl/azure/cognitive-services/speech/api-reference-rest/supportedlanguages
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set language
     * @url https://docs.microsoft.com/pl-pl/azure/cognitive-services/speech/api-reference-rest/supportedlanguages
     * @param string $value
     */
    public function setLanguage(string $value)
    {
        $this->language = $value;
    }

    /**
     * Get locale
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Set locale
     * @param string $value
     */
    public function setLocale(string $value)
    {
        $this->locale = $value;
    }

    /**
     * Get recognition_mode
     * @url https://docs.microsoft.com/pl-pl/azure/cognitive-services/speech/concepts#recognition-modes
     * @return string
     */
    public function getRecognitionMode(): string
    {
        return $this->recognition_mode;
    }

    /**
     * Set recognition_mode
     * @url https://docs.microsoft.com/pl-pl/azure/cognitive-services/speech/concepts#recognition-modes
     * @param string $value
     */
    public function setRecognitionMode(string $value)
    {
        $this->recognition_mode = $value;
    }
}
