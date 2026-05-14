<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MyMemoryTranslateService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Traduit un texte du français vers la langue cible
     * Utilise l'API MyMemory (gratuite, sans clé)
     */
    public function translate(string $text, string $targetLanguage = 'en', string $sourceLanguage = 'fr'): string
    {
        if (empty($text) || $targetLanguage === $sourceLanguage) {
            return $text;
        }

        try {
            // MyMemory API URL
            $url = 'https://api.mymemory.translated.net/get';
            $url .= '?q=' . urlencode($text);
            $url .= '&langpair=' . $sourceLanguage . '|' . $targetLanguage;
            $url .= '&de=a@b.com'; // Email requis (fictif mais valide)

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 30,
            ]);

            $data = $response->toArray();
           
            if (isset($data['responseData']['translatedText'])) {
                $translatedText = $data['responseData']['translatedText'];
               
                // MyMemory retourne parfois le texte original avec "******", on nettoie
                $translatedText = str_replace('******', '', $translatedText);
               
                if (!empty($translatedText) && $translatedText !== $text) {
                    return $translatedText;
                }
            }
           
            return $text;
        } catch (\Exception $e) {
            error_log('MyMemory error: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * Traduit plusieurs textes à la fois
     *
     * @param array<int|string, string> $texts
     * @return array<int|string, string>
     */
    public function translateMultiple(array $texts, string $targetLanguage = 'en', string $sourceLanguage = 'fr'): array
    {
        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLanguage, $sourceLanguage);
        }
        return $results;
    }
}
