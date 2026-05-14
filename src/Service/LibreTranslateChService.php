<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LibreTranslateChService
{
    private HttpClientInterface $httpClient;
    private string $apiUrl;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // Utiliser une API gratuite qui fonctionne
        $this->apiUrl = 'https://libretranslate.com/';
    }

    public function translate(string $text, string $targetLanguage = 'en', string $sourceLanguage = 'fr'): string
    {
        if (empty($text) || $targetLanguage === $sourceLanguage) {
            return $text;
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . 'translate', [
                'json' => [
                    'q' => $text,
                    'source' => $sourceLanguage,
                    'target' => $targetLanguage,
                    'format' => 'text',
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();
           
            if (isset($data['translatedText'])) {
                return $data['translatedText'];
            }
           
            return $text;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner le texte original
            return $text;
        }
    }
}
