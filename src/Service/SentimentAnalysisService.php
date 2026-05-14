<?php
// src/Service/SentimentAnalysisService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SentimentAnalysisService
{
    private HttpClientInterface $httpClient;
    private ?string $apiKey;

    public function __construct(HttpClientInterface $httpClient, ?string $apiKey = null)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * @return array{
     *     sentiment: string,
     *     confiance: float,
     *     positif: float,
     *     negatif: float,
     *     neutre: float
     * }
     */
    public function analyze(string $text): array
    {
        // Modèle multilingue spécialisé sentiment (très performant)
        $modelUrl = 'https://api-inference.huggingface.co/models/cardiffnlp/twitter-xlm-roberta-base-sentiment';
       
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];
           
            if ($this->apiKey !== null) {
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
            }
           
            $response = $this->httpClient->request('POST', $modelUrl, [
                'headers' => $headers,
                'json' => [
                    'inputs' => $text,
                ],
                'timeout' => 30,
            ]);

            $result = $response->toArray();
           
            // Le modèle retourne: [{"label": "positive", "score": 0.99}, ...]
            if (isset($result[0]) && is_array($result[0])) {
                $labels = $result[0];
                $positif = 0.0;
                $negatif = 0.0;
                $neutre = 0.0;
               
                foreach ($labels as $item) {
                    $label = strtolower($item['label'] ?? '');
                    $score = (float)($item['score'] ?? 0);
                   
                    if ($label === 'positive') {
                        $positif = $score;
                    } elseif ($label === 'negative') {
                        $negatif = $score;
                    } elseif ($label === 'neutral') {
                        $neutre = $score;
                    }
                }
               
                if ($positif > $negatif && $positif > $neutre) {
                    $sentiment = 'positif';
                    $confiance = round($positif * 100, 2);
                } elseif ($negatif > $positif && $negatif > $neutre) {
                    $sentiment = 'negatif';
                    $confiance = round($negatif * 100, 2);
                } else {
                    $sentiment = 'neutre';
                    $confiance = round($neutre * 100, 2);
                }
               
                return [
                    'sentiment' => $sentiment,
                    'confiance' => $confiance,
                    'positif' => round($positif * 100, 2),
                    'negatif' => round($negatif * 100, 2),
                    'neutre' => round($neutre * 100, 2),
                ];
            }
           
            return ['sentiment' => 'neutre', 'confiance' => 0.0, 'positif' => 0.0, 'negatif' => 0.0, 'neutre' => 0.0];
           
        } catch (\Exception $e) {
            // En cas d'erreur, on simule une analyse basée sur des mots-clés
            return $this->analyzeWithKeywords($text);
        }
    }
   
    /**
     * Méthode de fallback : analyse par mots-clés
     *
     * @return array{
     *     sentiment: string,
     *     confiance: float,
     *     positif: int,
     *     negatif: int,
     *     neutre: int
     * }
     */
    private function analyzeWithKeywords(string $text): array
    {
        $text = strtolower($text);
       
        // Mots-clés négatifs
        $negatifKeywords = [
            'déçu', 'décevant', 'déception', 'mauvais', 'mauvaise', 'sale', 'poussiéreux',
            'désagréable', 'impoli', 'catastrophique', 'insalubre', 'dégoûtant', 'vétuste',
            'incompétent', 'insupportable', 'fuir', 'jamais', 'déconseille', 'horrible',
            'médiocre', 'problème', 'erreur', 'lent', 'long', 'attente', 'mauvais'
        ];
       
        // Mots-clés positifs
        $positifKeywords = [
            'super', 'excellent', 'parfait', 'génial', 'propre', 'agréable', 'souriant',
            'professionnel', 'rapide', 'recommande', 'magnifique', 'bien', 'bon', 'très bien',
            'satisfait', 'confortable', 'calme', 'spacieux', 'accueillant'
        ];
       
        $negatifScore = 0;
        $positifScore = 0;
       
        foreach ($negatifKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $negatifScore++;
            }
        }
       
        foreach ($positifKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $positifScore++;
            }
        }
       
        // ✅ Correction : suppression de la condition > 0 qui est toujours vraie
        if ($negatifScore > $positifScore) {
            $confiance = min($negatifScore * 20, 95);
            return [
                'sentiment' => 'negatif',
                'confiance' => (float)$confiance,
                'positif' => 0,
                'negatif' => $confiance,
                'neutre' => 0
            ];
        } elseif ($positifScore > $negatifScore) {
            $confiance = min($positifScore * 20, 95);
            return [
                'sentiment' => 'positif',
                'confiance' => (float)$confiance,
                'positif' => $confiance,
                'negatif' => 0,
                'neutre' => 0
            ];
        } else {
            return [
                'sentiment' => 'neutre',
                'confiance' => 50.0,
                'positif' => 0,
                'negatif' => 0,
                'neutre' => 0
            ];
        }
    }
}
