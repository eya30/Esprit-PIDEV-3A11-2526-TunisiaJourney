<?php
// src/Service/SentimentAnalysisService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SentimentAnalysisService
{
<<<<<<< HEAD
    private HttpClientInterface $httpClient;
    private ?string $apiKey;

    public function __construct(HttpClientInterface $httpClient, ?string $apiKey = null)
=======
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey = null)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

<<<<<<< HEAD
    /**
     * @return array{
     *     sentiment: string,
     *     confiance: float,
     *     positif: float,
     *     negatif: float,
     *     neutre: float
     * }
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function analyze(string $text): array
    {
        // Modèle multilingue spécialisé sentiment (très performant)
        $modelUrl = 'https://api-inference.huggingface.co/models/cardiffnlp/twitter-xlm-roberta-base-sentiment';
<<<<<<< HEAD
       
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];
           
            if ($this->apiKey !== null) {
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
            }
           
            $response = $this->httpClient->request('POST', $modelUrl, [
                'headers' => $headers,
=======
        
        try {
            $response = $this->httpClient->request('POST', $modelUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                'json' => [
                    'inputs' => $text,
                ],
                'timeout' => 30,
            ]);

            $result = $response->toArray();
<<<<<<< HEAD
           
            // Le modèle retourne: [{"label": "positive", "score": 0.99}, ...]
            if (isset($result[0]) && is_array($result[0])) {
                $labels = $result[0];
                $positif = 0.0;
                $negatif = 0.0;
                $neutre = 0.0;
               
                foreach ($labels as $item) {
                    $label = strtolower($item['label'] ?? '');
                    $score = (float)($item['score'] ?? 0);
                   
=======
            
            // Le modèle retourne: [{"label": "positive", "score": 0.99}, ...]
            if (isset($result[0]) && is_array($result[0])) {
                $labels = $result[0];
                $positif = 0;
                $negatif = 0;
                $neutre = 0;
                
                foreach ($labels as $item) {
                    $label = strtolower($item['label'] ?? '');
                    $score = $item['score'] ?? 0;
                    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                    if ($label === 'positive') {
                        $positif = $score;
                    } elseif ($label === 'negative') {
                        $negatif = $score;
                    } elseif ($label === 'neutral') {
                        $neutre = $score;
                    }
                }
<<<<<<< HEAD
               
=======
                
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
               
=======
                
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                return [
                    'sentiment' => $sentiment,
                    'confiance' => $confiance,
                    'positif' => round($positif * 100, 2),
                    'negatif' => round($negatif * 100, 2),
                    'neutre' => round($neutre * 100, 2),
                ];
            }
<<<<<<< HEAD
           
            return ['sentiment' => 'neutre', 'confiance' => 0.0, 'positif' => 0.0, 'negatif' => 0.0, 'neutre' => 0.0];
           
=======
            
            return ['sentiment' => 'neutre', 'confiance' => 0, 'positif' => 0, 'negatif' => 0];
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        } catch (\Exception $e) {
            // En cas d'erreur, on simule une analyse basée sur des mots-clés
            return $this->analyzeWithKeywords($text);
        }
    }
<<<<<<< HEAD
   
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
=======
    
    /**
     * Méthode de fallback : analyse par mots-clés
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    private function analyzeWithKeywords(string $text): array
    {
        $text = strtolower($text);
<<<<<<< HEAD
       
=======
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        // Mots-clés négatifs
        $negatifKeywords = [
            'déçu', 'décevant', 'déception', 'mauvais', 'mauvaise', 'sale', 'poussiéreux',
            'désagréable', 'impoli', 'catastrophique', 'insalubre', 'dégoûtant', 'vétuste',
            'incompétent', 'insupportable', 'fuir', 'jamais', 'déconseille', 'horrible',
            'médiocre', 'problème', 'erreur', 'lent', 'long', 'attente', 'mauvais'
        ];
<<<<<<< HEAD
       
=======
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        // Mots-clés positifs
        $positifKeywords = [
            'super', 'excellent', 'parfait', 'génial', 'propre', 'agréable', 'souriant',
            'professionnel', 'rapide', 'recommande', 'magnifique', 'bien', 'bon', 'très bien',
            'satisfait', 'confortable', 'calme', 'spacieux', 'accueillant'
        ];
<<<<<<< HEAD
       
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
=======
        
        $negatifScore = 0;
        $positifScore = 0;
        
        foreach ($negatifKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $negatifScore++;
            }
        }
        
        foreach ($positifKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $positifScore++;
            }
        }
        
        if ($negatifScore > $positifScore && $negatifScore > 0) {
            return ['sentiment' => 'negatif', 'confiance' => min($negatifScore * 20, 95), 'positif' => 0, 'negatif' => min($negatifScore * 20, 95)];
        } elseif ($positifScore > $negatifScore && $positifScore > 0) {
            return ['sentiment' => 'positif', 'confiance' => min($positifScore * 20, 95), 'positif' => min($positifScore * 20, 95), 'negatif' => 0];
        } else {
            return ['sentiment' => 'neutre', 'confiance' => 50, 'positif' => 0, 'negatif' => 0];
        }
    }
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
