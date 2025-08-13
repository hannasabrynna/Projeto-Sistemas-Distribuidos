<?php

namespace App\Services;

use Aws\Polly\PollyClient;
use Illuminate\Support\Facades\Storage;

class AmazonPollyService
{
    protected $client;

    public function __construct()
    {
        $this->client = new PollyClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'), // <- ESSENCIAL
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'verify' => 'C:/certs/cacert.pem',
            ]
        ]);
    }

    public function synthesizeSpeech(string $text, string $filename = null): string
    {
        $result = $this->client->synthesizeSpeech([
            'OutputFormat' => 'mp3',
            'Text' => $text,
            'TextType' => 'text',
            'VoiceId' => 'Ruth', // veja outras vozes disponíveis no console da AWS
            'Engine' => 'neural'
        ]);

        $filename = $filename ?? uniqid('audio_') . '.mp3';
        $path = 'audios/' . $filename;

        // Obtendo o stream de áudio retornado pelo Polly
        $audioStream = $result['AudioStream'];

        // Salvando o conteúdo no S3
        Storage::disk('s3')->put($path, $audioStream->__toString());

        return $path; // caminho salvo no banco
    }


    //Metodo para gerar o JSON com os tempos (para sincronizar o texto com o áudio)
    public function generateSpeechMarks(string $text, string $filename = null): string
    {
        $result = $this->client->synthesizeSpeech([
            'OutputFormat' => 'json',
            'Text' => $text,
            'TextType' => 'text',
            'VoiceId' => 'Ruth',
            'Engine' => 'neural',
            'SpeechMarkTypes' => ['sentence'],
        ]);

        $filename = $filename ?? uniqid('marks_') . '.json';
        $path = 'speechmarks/' . $filename;

        Storage::disk('s3')->put($path, $result['AudioStream']->getContents());

        return Storage::disk('s3')->url($path);
    }
}
