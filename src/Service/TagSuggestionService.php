<?php
// src/Service/TagSuggestionService.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TagSuggestionService
{
    private const AI_TAGGING_API_URL = 'https://aiautotagging.com/api/tag/text';
    
    // Liste étendue et diversifiée de tags par catégorie
    private array $tagCategories = [
        'lieux' => [
            'djerba', 'tunis', 'carthage', 'sidi-bou-said', 'sousse', 'hammamet',
            'nabeul', 'monastir', 'mahdia', 'tabarka', 'ain-draham', 'dougga',
            'el-jem', 'kairouan', 'tozeur', 'douz', 'matmata', 'chebika', 'tamerza',
            'bizerte', 'zarzis', 'kelibia', 'korba', 'hammam-lif', 'la-marsa'
        ],
        'activites' => [
            'balade', 'randonnee', 'trekking', 'quad', 'dromadaire', 'plongee',
            'snorkeling', 'kayak', 'voile', 'peche', 'golf', 'equitation', 'yoga',
            'surf', 'kitesurf', 'parachute', 'escalade', 'velo', 'safari'
        ],
        'nature' => [
            'plage', 'mer', 'desert', 'oasis', 'montagne', 'foret', 'lac-salin',
            'cascade', 'jardin', 'palmier', 'olivier', 'coucher-soleil', 'lever-soleil',
            'lagune', 'flamants-roses', 'iles', 'grottes', 'source-chaude'
        ],
        'culture' => [
            'musee', 'histoire', 'patrimoine', 'medina', 'souk', 'mosquee',
            'artisanat', 'poterie', 'tapisserie', 'musique', 'festival', 'cinema',
            'amphitheatre', 'ruines', 'mosaique', 'bibliotheque', 'galerie'
        ],
        'gastronomie' => [
            'couscous', 'brik', 'lablabi', 'harissa', 'olive', 'dattes',
            'gateau', 'the-mint', 'restaurant', 'street-food', 'cuisine',
            'poisson', 'fruits-mer', 'merguez', 'salade-méchouia', 'bambalouni'
        ],
        'sentiments' => [
            'aventure', 'decouverte', 'authentique', 'paisible', 'depaysement',
            'evasion', 'bien-etre', 'ressourcement', 'partage', 'inspiration',
            'detente', 'plaisir', 'etonnement', 'serenite', 'merveille'
        ],
        'pratique' => [
            'conseils', 'astuces', 'budget', 'transport', 'hebergement',
            'securite', 'saison', 'voyage-seul', 'famille', 'amis',
            'bagages', 'formalites', 'devise', 'langue', 'location-voiture'
        ],
        'hebergement' => [
            'hotel', 'riad', 'dar', 'resort', 'camping', 'auberge',
            'villa', 'appartement', 'glamping', 'ecolodge', 'guesthouse'
        ]
    ];
    
    // Tags de secours diversifiés
    private array $fallbackTagsPool = [
        'tunisie', 'voyage', 'decouverte', 'aventure', 'depaysement',
        'coucher-soleil', 'culture', 'gastronomie', 'plage', 'desert',
        'randonnee', 'patrimoine', 'artisanat', 'bien-etre', 'famille',
        'authentique', 'paisible', 'inspiration', 'souvenirs', 'exploration',
        'detente', 'evasion', 'bon-plan', 'insolite', 'magnifique'
    ];

    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;

    public function __construct(
        LoggerInterface $logger,
        HttpClientInterface $httpClient
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
    }

    public function suggestTags(string $text, ?string $existingTags = null, int $maxTags = 8): array
    {
        if (trim($text) === '') {
            return ['tags' => [], 'success' => false, 'error' => 'Texte vide.'];
        }

        $text = mb_substr($text, 0, 9000);
        $textWithContext = 'Voyage en Tunisie, tourisme, vacances, découverte: ' . $text;

        try {
            $response = $this->httpClient->request('POST', self::AI_TAGGING_API_URL, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'text'    => $textWithContext,
                    'options' => [
                        'maxTags'           => min($maxTags + 3, 20),
                        'language'          => 'fr',
                        'includeConfidence' => true,
                    ],
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $this->logger->warning('AI Auto Tagging API error: HTTP ' . $statusCode);
                return $this->diversifiedFallbackTags($text, $existingTags, $maxTags);
            }

            $data = $response->toArray(false);

            if (!isset($data['tags']) || !is_array($data['tags'])) {
                $this->logger->warning('AI Auto Tagging API invalid response format');
                return $this->diversifiedFallbackTags($text, $existingTags, $maxTags);
            }

            $rawTags = [];
            foreach ($data['tags'] as $tagItem) {
                if (is_array($tagItem) && isset($tagItem['label'])) {
                    $rawTags[] = $tagItem['label'];
                } elseif (is_string($tagItem)) {
                    $rawTags[] = $tagItem;
                }
            }

            $tags = $this->sanitizeTags($rawTags, $maxTags + 2);
            $tags = $this->diversifyTags($tags, $text, $maxTags);

            if (!empty($existingTags)) {
                $existingTagArray = array_map('trim', explode(',', mb_strtolower($existingTags)));
                $tags = array_values(array_filter($tags, function ($tag) use ($existingTagArray) {
                    return !in_array(mb_strtolower($tag), $existingTagArray, true);
                }));
                $tags = array_slice($tags, 0, $maxTags);
            }

            if (empty($tags)) {
                return $this->diversifiedFallbackTags($text, $existingTags, $maxTags);
            }

            return ['tags' => $tags, 'success' => true, 'error' => null];

        } catch (\Throwable $e) {
            $this->logger->warning('TagSuggestionService error: ' . $e->getMessage());
            return $this->diversifiedFallbackTags($text, $existingTags, $maxTags);
        }
    }

    private function diversifyTags(array $tags, string $text, int $maxTags): array
    {
        $textLower = mb_strtolower($text);
        $diversified = [];
        $usedCategories = [];
        
        $relevantCategories = $this->detectRelevantCategories($textLower);
        
        foreach ($tags as $tag) {
            if (count($diversified) >= $maxTags) break;
            if (!in_array($tag, $diversified, true)) {
                $diversified[] = $tag;
                $category = $this->findTagCategory($tag);
                if ($category) $usedCategories[$category] = true;
            }
        }
        
        foreach ($relevantCategories as $category => $priority) {
            if (count($diversified) >= $maxTags) break;
            if (isset($usedCategories[$category])) continue;
            
            $availableTags = $this->tagCategories[$category] ?? [];
            $availableTags = array_diff($availableTags, $diversified);
            
            if (!empty($availableTags)) {
                $randomTag = $availableTags[array_rand($availableTags)];
                $diversified[] = $randomTag;
                $usedCategories[$category] = true;
            }
        }
        
        if (count($diversified) < $maxTags) {
            $genericTags = $this->fallbackTagsPool;
            $genericTags = array_diff($genericTags, $diversified);
            shuffle($genericTags);
            $needed = $maxTags - count($diversified);
            $diversified = array_merge($diversified, array_slice($genericTags, 0, $needed));
        }
        
        return array_slice($diversified, 0, $maxTags);
    }

    private function detectRelevantCategories(string $text): array
    {
        $relevance = [];
        $keywords = [
            'lieux' => ['djerba', 'tunis', 'carthage', 'sidi bou', 'sousse', 'hammamet', 'monastir', 'tabarka', 'dougga', 'el jem', 'kairouan', 'tozeur', 'douz', 'matmata', 'ville', 'region', 'ile'],
            'activites' => ['randonnee', 'trekking', 'plongee', 'snorkeling', 'kayak', 'voile', 'peche', 'golf', 'quad', 'dromadaire', 'equitation', 'yoga', 'activite', 'sport', 'bateau'],
            'nature' => ['plage', 'mer', 'desert', 'oasis', 'montagne', 'foret', 'cascade', 'palmier', 'olivier', 'coucher', 'soleil', 'paysage', 'nature', 'sable', 'dune'],
            'culture' => ['musee', 'histoire', 'patrimoine', 'medina', 'souk', 'mosquee', 'artisanat', 'poterie', 'musique', 'festival', 'culturel', 'tradition'],
            'gastronomie' => ['couscous', 'brik', 'lablabi', 'harissa', 'olive', 'dattes', 'restaurant', 'manger', 'cuisine', 'plat', 'specialite', 'degustation'],
            'sentiments' => ['aventure', 'decouverte', 'authentique', 'paisible', 'depaysement', 'evasion', 'ressourcement', 'partage', 'inspiration', 'magnifique', 'superbe'],
            'pratique' => ['conseil', 'astuce', 'budget', 'transport', 'hebergement', 'securite', 'saison', 'prix', 'payer', 'voyager', 'tarif'],
            'hebergement' => ['hotel', 'riad', 'villa', 'camping', 'auberge', 'resort', 'logement', 'nuit', 'sejour']
        ];
        
        foreach ($keywords as $category => $words) {
            $score = 0;
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $relevance[$category] = $score;
            }
        }
        
        arsort($relevance);
        
        if (empty($relevance)) {
            return ['nature' => 1, 'sentiments' => 1, 'pratique' => 1];
        }
        
        return $relevance;
    }

    private function findTagCategory(string $tag): ?string
    {
        foreach ($this->tagCategories as $category => $tags) {
            if (in_array($tag, $tags, true)) {
                return $category;
            }
        }
        return null;
    }

    private function sanitizeTags(array $raw, int $max): array
    {
        $clean = [];
        foreach ($raw as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $tag = mb_strtolower($tag);
            $tag = preg_replace('/[^a-z0-9éèêëàâäôöûüç\-_]/u', '', $tag);
            $tag = preg_replace('/-+/', '-', $tag);
            $tag = trim($tag, '-');

            if ($tag !== '' && strlen($tag) >= 2 && strlen($tag) <= 25) {
                if (!in_array($tag, $clean, true)) {
                    $clean[] = $tag;
                }
            }
        }
        return array_slice($clean, 0, $max);
    }

    private function diversifiedFallbackTags(string $text, ?string $existingTags, int $maxTags): array
    {
        $textLower = mb_strtolower($text);
        $existing = $existingTags
            ? array_map('trim', explode(',', mb_strtolower($existingTags)))
            : [];
        
        $found = [];
        $usedCategories = [];
        
        $relevantCategories = $this->detectRelevantCategories($textLower);
        
        foreach ($relevantCategories as $category => $priority) {
            if (count($found) >= $maxTags) break;
            if (isset($usedCategories[$category])) continue;
            
            $availableTags = $this->tagCategories[$category] ?? [];
            $availableTags = array_filter($availableTags, function($tag) use ($existing, $found) {
                return !in_array($tag, $existing, true) && !in_array($tag, $found, true);
            });
            
            if (!empty($availableTags)) {
                $numToTake = min($priority, 2);
                $selectedKeys = (array) array_rand($availableTags, min($numToTake, count($availableTags)));
                foreach ($selectedKeys as $key) {
                    $tag = $availableTags[$key];
                    if (!in_array($tag, $found, true)) {
                        $found[] = $tag;
                    }
                }
                $usedCategories[$category] = true;
            }
        }
        
        if (count($found) < $maxTags) {
            $genericTags = $this->fallbackTagsPool;
            $genericTags = array_diff($genericTags, $existing);
            $genericTags = array_diff($genericTags, $found);
            shuffle($genericTags);
            $needed = $maxTags - count($found);
            $found = array_merge($found, array_slice($genericTags, 0, $needed));
        }
        
        $tags = array_slice(array_unique($found), 0, $maxTags);
        
        return [
            'tags'    => $tags,
            'success' => true,
            'error'   => null,
        ];
    }
}