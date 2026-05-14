<?php
<<<<<<< HEAD

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
<<<<<<< HEAD
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(ParameterBagInterface $params, HttpClientInterface $httpClient)
    {
        $value = $params->get('gemini_api_key');
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Le paramètre "gemini_api_key" doit être une chaîne de caractères.');
        }
        $this->apiKey = $value;
        $this->httpClient = $httpClient;
    }

    /**
     * @param array<string, string> $options
     */
    public function generateProductDescription(string $productName, array $options = []): string
    {
        if (empty($this->apiKey) || $this->apiKey === 'test_key_pour_le_moment') {
            return $this->getMockDescription($productName, $options);
        }

        try {
            $prompt = $this->buildProductPrompt($productName, $options);

=======
    private $apiKey;
    private $httpClient;
    
    public function __construct(ParameterBagInterface $params, HttpClientInterface $httpClient)
    {
        $this->apiKey = $params->get('gemini_api_key');
        $this->httpClient = $httpClient;
    }
    
    /**
     * Générer une description de produit avec l'API Gemini
     */
    public function generateProductDescription(string $productName, array $options = []): string
    {
        // Vérifier si on a une vraie clé API
        if (empty($this->apiKey) || $this->apiKey === 'test_key_pour_le_moment') {
            return $this->getMockDescription($productName, $options);
        }
        
        try {
            $prompt = $this->buildProductPrompt($productName, $options);
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
<<<<<<< HEAD
                        'temperature'    => 0.7,
=======
                        'temperature' => 0.7,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                        'maxOutputTokens' => 500,
                    ]
                ],
                'timeout' => 30,
            ]);
<<<<<<< HEAD

            $data = $response->toArray();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $this->cleanDescription($data['candidates'][0]['content']['parts'][0]['text']);
            }

            return $this->getMockDescription($productName, $options);

=======
            
            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $this->cleanDescription($data['candidates'][0]['content']['parts'][0]['text']);
            }
            
            return $this->getMockDescription($productName, $options);
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        } catch (\Exception $e) {
            return $this->getMockDescription($productName, $options);
        }
    }
<<<<<<< HEAD

    /**
     * @param array<string, string> $options
=======
    
    /**
     * Description de secours (sans API)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    private function getMockDescription(string $productName, array $options = []): string
    {
        $descriptions = [
            "Découvrez notre magnifique {$productName}, un produit artisanal tunisien authentique. Fabriqué à la main avec des matériaux de qualité, il apporte une touche traditionnelle à votre quotidien.",
<<<<<<< HEAD
            "Le {$productName} est un trésor de l'artisanat tunisien. Chaque pièce est unique et raconte une histoire transmise de génération en génération.",
            "Authentique et de qualité supérieure, le {$productName} est fabriqué selon les méthodes traditionnelles tunisiennes. Idéal pour les amateurs d'artisanat.",
            "Ce {$productName} incarne l'excellence de l'artisanat tunisien. Chaque détail est soigneusement travaillé à la main par des artisans passionnés."
        ];

        $description = $descriptions[array_rand($descriptions)];

        if (!empty($options['category'])) {
            $description .= " Catégorie: {$options['category']}.";
        }

        if (!empty($options['origin'])) {
            $description .= " Originaire de {$options['origin']}, une région réputée pour son artisanat.";
        }

        return $description;
    }

=======
            
            "Le {$productName} est un trésor de l'artisanat tunisien. Chaque pièce est unique et raconte une histoire transmise de génération en génération.",
            
            "Authentique et de qualité supérieure, le {$productName} est fabriqué selon les méthodes traditionnelles tunisiennes. Idéal pour les amateurs d'artisanat.",
            
            "Ce {$productName} incarne l'excellence de l'artisanat tunisien. Chaque détail est soigneusement travaillé à la main par des artisans passionnés."
        ];
        
        $description = $descriptions[array_rand($descriptions)];
        
        if (!empty($options['category'])) {
            $description .= " Catégorie: {$options['category']}.";
        }
        
        if (!empty($options['origin'])) {
            $description .= " Originaire de {$options['origin']}, une région réputée pour son artisanat.";
        }
        
        return $description;
    }
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function generateSEODescription(string $productName, string $category = ''): string
    {
        if (empty($this->apiKey) || $this->apiKey === 'test_key_pour_le_moment') {
            $seos = [
                "Achetez {$productName} artisanal tunisien de qualité. Fabrication traditionnelle, livraison rapide.",
                "Découvrez {$productName}, un produit authentique tunisien. Idéal pour un cadeau original.",
                "Le meilleur {$productName} en Tunisie. Authentique, fait main, traditionnel."
            ];
            return $seos[array_rand($seos)];
        }
<<<<<<< HEAD

        try {
            $prompt = "Génère une description SEO (150-160 caractères) pour un produit artisanal tunisien appelé '{$productName}'. Réponds uniquement en français.";

=======
        
        try {
            $prompt = "Génère une description SEO (150-160 caractères) pour un produit artisanal tunisien appelé '{$productName}'. Réponds uniquement en français.";
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]
            ]);
<<<<<<< HEAD

            $data = $response->toArray();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }

            return "Découvrez notre {$productName} artisanal tunisien de qualité.";

=======
            
            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }
            
            return "Découvrez notre {$productName} artisanal tunisien de qualité.";
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        } catch (\Exception $e) {
            return "Découvrez notre {$productName} artisanal tunisien de qualité.";
        }
    }
<<<<<<< HEAD

    /**
     * @return array<int, string>
     */
=======
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function suggestKeywords(string $productName): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'test_key_pour_le_moment') {
            return [
                $productName,
                'artisanat tunisien',
                'fait main',
                'produit traditionnel',
                'cadeau original'
            ];
        }
<<<<<<< HEAD

        try {
            $prompt = "Pour le produit '{$productName}', génère 5 mots-clés SEO pertinents en français, séparés par des virgules.";

=======
        
        try {
            $prompt = "Pour le produit '{$productName}', génère 5 mots-clés SEO pertinents en français, séparés par des virgules.";
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]
            ]);
<<<<<<< HEAD

            $data = $response->toArray();

=======
            
            $data = $response->toArray();
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $keywords = explode(',', $data['candidates'][0]['content']['parts'][0]['text']);
                return array_map('trim', $keywords);
            }
<<<<<<< HEAD

            return [$productName, 'artisanat tunisien', 'fait main'];

=======
            
            return [$productName, 'artisanat tunisien', 'fait main'];
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        } catch (\Exception $e) {
            return [$productName, 'artisanat tunisien', 'fait main'];
        }
    }
<<<<<<< HEAD

    /**
     * @param array<int, string> $features
     */
=======
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function generateDetailedDescription(string $productName, array $features = []): string
    {
        $description = "Le {$productName} est un chef-d'œuvre de l'artisanat tunisien. ";
        $description .= "Chaque pièce est fabriquée avec soin par des artisans expérimentés. ";
<<<<<<< HEAD

        if (!empty($features)) {
            $description .= "Caractéristiques : " . implode(', ', $features) . ". ";
        }

        $description .= "Ce produit authentique apportera une touche d'élégance à votre quotidien.";

        return $description;
    }

    public function optimizeDescription(string $currentDescription, string $productName): string
    {
        $optimized = trim($currentDescription);

        if (!str_contains($optimized, $productName)) {
            $optimized = $productName . " - " . $optimized;
        }

        return $optimized;
    }

    /**
     * @param array<string, string> $options
     */
=======
        
        if (!empty($features)) {
            $description .= "Caractéristiques : " . implode(', ', $features) . ". ";
        }
        
        $description .= "Ce produit authentique apportera une touche d'élégance à votre quotidien.";
        
        return $description;
    }
    
    public function optimizeDescription(string $currentDescription, string $productName): string
    {
        $optimized = trim($currentDescription);
        
        if (!str_contains($optimized, $productName)) {
            $optimized = $productName . " - " . $optimized;
        }
        
        return $optimized;
    }
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function buildProductPrompt(string $productName, array $options): string
    {
        $prompt = "Tu es un expert en produits artisanaux tunisiens. ";
        $prompt .= "Génère une belle description (80-120 mots) pour un produit appelé '{$productName}'. ";
<<<<<<< HEAD

        if (!empty($options['category'])) {
            $prompt .= "Catégorie: {$options['category']}. ";
        }

        $prompt .= "La description doit être en français, authentique et chaleureuse. ";
        $prompt .= "Réponds uniquement avec la description, sans introduction.";

        return $prompt;
    }

    private function cleanDescription(string $description): string
    {
        $description = (string) preg_replace('/\*+/', '', $description);
        $description = (string) preg_replace('/#+/', '', $description);
        $description = (string) preg_replace('/\s+/', ' ', $description);
=======
        
        if (!empty($options['category'])) {
            $prompt .= "Catégorie: {$options['category']}. ";
        }
        
        $prompt .= "La description doit être en français, authentique et chaleureuse. ";
        $prompt .= "Réponds uniquement avec la description, sans introduction.";
        
        return $prompt;
    }
    
    private function cleanDescription(string $description): string
    {
        $description = preg_replace('/\*+/', '', $description);
        $description = preg_replace('/#+/', '', $description);
        $description = preg_replace('/\s+/', ' ', $description);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return trim($description);
    }
}