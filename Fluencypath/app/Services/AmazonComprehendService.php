<?php

namespace App\Services;

use Aws\Comprehend\ComprehendClient;
use Illuminate\Support\Facades\Storage;

class AmazonComprehendService
{
    protected $client;

    public function __construct()
    {
        $this->client = new ComprehendClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function detectEntities(string $text, string $language = 'pt'): array
{
        $result = $this->client->detectKeyPhrases([
            'LanguageCode' => $language,
            'Text' => $text,
        ]);

        // Extrair apenas os textos das frases-chave
        $phrases = collect($result['KeyPhrases'])
            ->pluck('Text')
            ->map(function ($phrase) {
                return ['value' => $phrase];
            })
            ->unique('value') // remove duplicadas
            ->take(5)         // limita a 5 tags
            ->values()
            ->all();

        return $phrases;
}
}