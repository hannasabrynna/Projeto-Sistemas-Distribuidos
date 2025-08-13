<?php

namespace App\Services;

use Aws\Translate\TranslateClient;

class AmazonTranslateService
{
    protected $client;

    public function __construct()
    {
        $this->client = new TranslateClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function translateText(string $text, string $sourceLang = 'en', string $targetLang = 'pt')
    {
        $result = $this->client->translateText([
            'SourceLanguageCode' => $sourceLang,
            'TargetLanguageCode' => $targetLang,
            'Text' => $text,
        ]);

        return $result['TranslatedText'];
    }
}
