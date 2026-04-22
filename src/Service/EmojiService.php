<?php
// src/Service/EmojiService.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service Emoji basé sur l'API EmojiHub (gratuite, sans clé API)
 * Documentation : https://github.com/cheatsnake/emojihub
 * Base URL       : https://emojihub.yurace.pro/api
 */
class EmojiService
{
    private const BASE_URL = 'https://emojihub.yurace.pro/api';
    private const TIMEOUT  = 6;

    /**
     * Mapping catégories lisibles → slugs EmojiHub
     * GET /api/all/category/{category-name}
     */
    private const CATEGORY_MAP = [
        'smileys-and-people' => 'smileys-and-people',
        'animals-and-nature' => 'animals-and-nature',
        'food-and-drink'     => 'food-and-drink',
        'travel-and-places'  => 'travel-and-places',
        'activities'         => 'activities',
        'objects'            => 'objects',
        'symbols'            => 'symbols',
        'flags'              => 'flags',
    ];

    /**
     * Groupes EmojiHub utiles pour le voyage / tourisme
     * GET /api/all/group/{group-name}
     */
    private const TRAVEL_GROUPS = [
        'travel-and-places', // groupe principal voyage
        'food-prepared',
        'food-fruit',
        'animal-bird',
        'animal-mammal',
        'plant-flower',
        'face-positive',
        'emotion',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger
    ) {}

    // =========================================================================
    // API publique
    // =========================================================================

    /**
     * Retourne des emojis liés au voyage (catégorie travel + quelques extras).
     */
    public function getTravelEmojis(int $limit = 30): array
    {
        // 1. Essayer la catégorie travel-and-places
        $emojis = $this->fetchCategory('travel-and-places', $limit);

        // 2. Compléter avec des emojis aléatoires si besoin
        if (count($emojis) < $limit) {
            $extra = $this->fetchRandom(max(1, $limit - count($emojis)));
            $emojis = array_merge($emojis, $extra);
        }

        if (empty($emojis)) {
            return $this->getFallbackEmojis();
        }

        return array_slice($this->dedup($emojis), 0, $limit);
    }

    /**
     * Recherche des emojis par nom (via /api/all + filtre local).
     * EmojiHub ne propose pas d'endpoint de recherche par mot-clé,
     * on télécharge donc la liste complète et on filtre localement.
     */
    public function searchEmojis(string $query, int $limit = 20): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return $this->getTravelEmojis($limit);
        }

        // Tenter de mapper la requête à une catégorie EmojiHub connue
        $category = $this->mapQueryToCategory($query);
        if ($category !== null) {
            $emojis = $this->fetchCategory($category, $limit * 2);
            if (!empty($emojis)) {
                return array_slice($emojis, 0, $limit);
            }
        }

        // Sinon : télécharger tous les emojis et filtrer par nom
        $all     = $this->fetchAll(500);
        $results = array_filter($all, function (array $emoji) use ($query): bool {
            $name = mb_strtolower($emoji['slug'] ?? $emoji['name'] ?? '');
            return str_contains($name, $query);
        });

        $results = array_values($results);

        if (empty($results)) {
            return $this->searchFallback($query);
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Retourne des emojis populaires (smileys + travel mélangés).
     */
    public function getTrendingEmojis(int $limit = 30): array
    {
        $smiley  = $this->fetchCategory('smileys-and-people', 15);
        $travel  = $this->fetchCategory('travel-and-places', 15);
        $all     = array_merge($smiley, $travel);

        if (empty($all)) {
            return $this->getFallbackEmojis();
        }

        shuffle($all);
        return array_slice($this->dedup($all), 0, $limit);
    }

    /**
     * Retourne les emojis d'une catégorie EmojiHub.
     */
    public function getEmojisByCategory(string $category, int $limit = 30): array
    {
        $slug   = self::CATEGORY_MAP[$category] ?? $category;
        $emojis = $this->fetchCategory($slug, $limit);

        if (empty($emojis)) {
            return $this->getFallbackEmojis();
        }

        return array_slice($emojis, 0, $limit);
    }

    /**
     * Retourne les catégories disponibles sur EmojiHub.
     */
    public function getCategories(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/categories', [
                'timeout' => self::TIMEOUT,
            ]);
            if ($response->getStatusCode() === 200) {
                return $response->toArray(false) ?: [];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('EmojiHub categories error: ' . $e->getMessage());
        }
        return array_keys(self::CATEGORY_MAP);
    }

    /**
     * Retourne les groupes disponibles sur EmojiHub.
     */
    public function getGroups(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/groups', [
                'timeout' => self::TIMEOUT,
            ]);
            if ($response->getStatusCode() === 200) {
                return $response->toArray(false) ?: [];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('EmojiHub groups error: ' . $e->getMessage());
        }
        return self::TRAVEL_GROUPS;
    }

    // =========================================================================
    // Méthodes privées — appels API EmojiHub
    // =========================================================================

    /**
     * GET /api/random
     * Retourne un seul emoji aléatoire.
     */
    private function fetchOneRandom(): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/random', [
                'timeout' => self::TIMEOUT,
            ]);
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray(false);
                return is_array($data) ? $this->normalize($data) : null;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('EmojiHub random error: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Appelle /api/random plusieurs fois pour obtenir $count emojis.
     */
    private function fetchRandom(int $count): array
    {
        $results = [];
        $maxTries = $count * 2;
        for ($i = 0; $i < $maxTries && count($results) < $count; $i++) {
            $emoji = $this->fetchOneRandom();
            if ($emoji !== null) {
                $results[] = $emoji;
            }
        }
        return $results;
    }

    /**
     * GET /api/all/category/{category}
     * Retourne tous les emojis d'une catégorie.
     */
    private function fetchCategory(string $category, int $limit): array
    {
        try {
            $url      = self::BASE_URL . '/all/category/' . urlencode($category);
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => self::TIMEOUT,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray(false);
            if (!is_array($data) || empty($data)) {
                return [];
            }

            $normalized = array_map([$this, 'normalize'], $data);
            $normalized = array_filter($normalized);
            $normalized = array_values($normalized);

            shuffle($normalized);
            return array_slice($normalized, 0, $limit);

        } catch (\Throwable $e) {
            $this->logger->warning("EmojiHub category '{$category}' error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * GET /api/all/group/{group}
     * Retourne tous les emojis d'un groupe.
     */
    private function fetchGroup(string $group, int $limit): array
    {
        try {
            $url      = self::BASE_URL . '/all/group/' . urlencode($group);
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => self::TIMEOUT,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray(false);
            if (!is_array($data) || empty($data)) {
                return [];
            }

            $normalized = array_map([$this, 'normalize'], $data);
            $normalized = array_filter($normalized);
            $normalized = array_values($normalized);

            shuffle($normalized);
            return array_slice($normalized, 0, $limit);

        } catch (\Throwable $e) {
            $this->logger->warning("EmojiHub group '{$group}' error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * GET /api/all
     * Retourne tous les emojis (1791 objets).
     * Utilisé pour la recherche locale.
     */
    private function fetchAll(int $limit = 200): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/all', [
                'timeout' => 12,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray(false);
            if (!is_array($data)) {
                return [];
            }

            $normalized = array_map([$this, 'normalize'], $data);
            $normalized = array_filter($normalized);
            return array_values($normalized);

        } catch (\Throwable $e) {
            $this->logger->warning('EmojiHub all error: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // Normalisation
    // =========================================================================

    /**
     * Convertit un objet EmojiHub en tableau normalisé pour le front.
     *
     * Réponse EmojiHub :
     * {
     *   "name": "hugging face",
     *   "category": "smileys and people",
     *   "group": "face positive",
     *   "htmlCode": ["&#129303;"],
     *   "unicode": ["U+1F917"]
     * }
     *
     * On retourne :
     * {
     *   "character": "🤗",
     *   "slug": "hugging-face",
     *   "name": "hugging face",
     *   "category": "smileys and people",
     *   "group": "face positive",
     *   "htmlCode": "&#129303;",
     *   "unicode": "U+1F917"
     * }
     */
    private function normalize(mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        // Décoder le htmlCode en caractère emoji
        $htmlCodes = $raw['htmlCode'] ?? [];
        if (!is_array($htmlCodes) || empty($htmlCodes)) {
            return null;
        }

        // html_entity_decode ne fonctionne pas toujours sur les entités numériques
        // On utilise mb_convert_encoding + html_entity_decode
        $character = '';
        foreach ($htmlCodes as $code) {
            $decoded = html_entity_decode((string) $code, ENT_HTML5, 'UTF-8');
            if ($decoded === '') {
                // Fallback : parser manuellement &#NNNNN;
                if (preg_match('/&#(\d+);/', (string) $code, $m)) {
                    $decoded = mb_chr((int) $m[1], 'UTF-8');
                }
            }
            $character .= $decoded;
        }

        if ($character === '') {
            return null;
        }

        $name = $raw['name'] ?? '';
        $slug = str_replace(' ', '-', strtolower(trim($name)));

        return [
            'character' => $character,
            'slug'      => $slug,
            'name'      => $name,
            'category'  => $raw['category'] ?? '',
            'group'     => $raw['group']    ?? '',
            'htmlCode'  => implode('', $htmlCodes),
            'unicode'   => implode(' ', (array) ($raw['unicode'] ?? [])),
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Tente de mapper une requête de recherche vers une catégorie EmojiHub.
     */
    private function mapQueryToCategory(string $query): ?string
    {
        $map = [
            'voyage'    => 'travel-and-places',
            'travel'    => 'travel-and-places',
            'avion'     => 'travel-and-places',
            'plage'     => 'travel-and-places',
            'montagne'  => 'travel-and-places',
            'hotel'     => 'travel-and-places',
            'food'      => 'food-and-drink',
            'nourriture'=> 'food-and-drink',
            'manger'    => 'food-and-drink',
            'animal'    => 'animals-and-nature',
            'nature'    => 'animals-and-nature',
            'sport'     => 'activities',
            'activite'  => 'activities',
            'smiley'    => 'smileys-and-people',
            'visage'    => 'smileys-and-people',
            'flag'      => 'flags',
            'drapeau'   => 'flags',
            'symbole'   => 'symbols',
        ];

        foreach ($map as $keyword => $category) {
            if (str_contains($query, $keyword)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Déduplique les emojis par caractère.
     */
    private function dedup(array $emojis): array
    {
        $seen  = [];
        $clean = [];
        foreach ($emojis as $e) {
            $char = $e['character'] ?? '';
            if ($char !== '' && !isset($seen[$char])) {
                $seen[$char] = true;
                $clean[]     = $e;
            }
        }
        return $clean;
    }

    /**
     * Recherche locale dans la liste de fallback.
     */
    private function searchFallback(string $query): array
    {
        $all     = $this->getExtendedFallbackEmojis();
        $results = array_filter($all, function (array $e) use ($query): bool {
            return str_contains($e['slug'] ?? '', $query)
                || str_contains($e['group'] ?? '', $query)
                || str_contains($e['name'] ?? '', $query);
        });

        return array_values($results) ?: array_slice($all, 0, 10);
    }

    /**
     * Retourne une liste de secours si l'API EmojiHub est indisponible.
     */
    public function getFallbackEmojis(): array
    {
        return array_slice($this->getExtendedFallbackEmojis(), 0, 30);
    }

    private function getExtendedFallbackEmojis(): array
    {
        return [
            // Smileys
            ['character'=>'😊','slug'=>'smiling-face',         'name'=>'smiling face',          'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'😍','slug'=>'heart-eyes',            'name'=>'heart eyes',             'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'😂','slug'=>'tears-of-joy',          'name'=>'tears of joy',           'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'🤩','slug'=>'star-struck',           'name'=>'star struck',            'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'😎','slug'=>'cool-sunglasses',       'name'=>'cool sunglasses',        'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'🥰','slug'=>'smiling-hearts',        'name'=>'smiling hearts',         'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'😄','slug'=>'grinning-face',         'name'=>'grinning face',          'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'🤗','slug'=>'hugging-face',          'name'=>'hugging face',           'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'😇','slug'=>'angel-face',            'name'=>'angel face',             'category'=>'smileys and people','group'=>'face positive'],
            ['character'=>'🥳','slug'=>'party-face',            'name'=>'party face',             'category'=>'smileys and people','group'=>'face positive'],
            // Travel
            ['character'=>'✈️','slug'=>'airplane',              'name'=>'airplane',               'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🌍','slug'=>'globe-africa',          'name'=>'globe africa',           'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🏖️','slug'=>'beach-umbrella',        'name'=>'beach umbrella',         'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🏝️','slug'=>'desert-island',         'name'=>'desert island',          'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🏨','slug'=>'hotel',                 'name'=>'hotel',                  'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🗺️','slug'=>'world-map',             'name'=>'world map',              'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🧳','slug'=>'luggage',               'name'=>'luggage',                'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🚗','slug'=>'automobile',            'name'=>'automobile',             'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'⛰️','slug'=>'mountain',              'name'=>'mountain',               'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🗼','slug'=>'eiffel-tower',          'name'=>'eiffel tower',           'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🌴','slug'=>'palm-tree',             'name'=>'palm tree',              'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🏕️','slug'=>'camping',               'name'=>'camping',                'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🚢','slug'=>'ship',                  'name'=>'ship',                   'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🌅','slug'=>'sunrise',               'name'=>'sunrise',                'category'=>'travel and places', 'group'=>'travel and places'],
            ['character'=>'🌊','slug'=>'ocean-wave',            'name'=>'ocean wave',             'category'=>'travel and places', 'group'=>'travel and places'],
            // Food
            ['character'=>'🍕','slug'=>'pizza',                 'name'=>'pizza',                  'category'=>'food and drink',    'group'=>'food prepared'],
            ['character'=>'🍷','slug'=>'wine-glass',            'name'=>'wine glass',             'category'=>'food and drink',    'group'=>'drink'],
            ['character'=>'🥗','slug'=>'green-salad',           'name'=>'green salad',            'category'=>'food and drink',    'group'=>'food prepared'],
            ['character'=>'🍰','slug'=>'shortcake',             'name'=>'shortcake',              'category'=>'food and drink',    'group'=>'food sweet'],
            ['character'=>'☕','slug'=>'hot-beverage',          'name'=>'hot beverage',           'category'=>'food and drink',    'group'=>'drink'],
            // Symbols
            ['character'=>'❤️','slug'=>'red-heart',             'name'=>'red heart',              'category'=>'symbols',           'group'=>'emotion'],
            ['character'=>'🔥','slug'=>'fire',                  'name'=>'fire',                   'category'=>'symbols',           'group'=>'symbols'],
            ['character'=>'💯','slug'=>'hundred-points',        'name'=>'hundred points',         'category'=>'symbols',           'group'=>'symbols'],
            ['character'=>'⭐','slug'=>'star',                  'name'=>'star',                   'category'=>'symbols',           'group'=>'symbols'],
            ['character'=>'✨','slug'=>'sparkles',              'name'=>'sparkles',               'category'=>'symbols',           'group'=>'symbols'],
            ['character'=>'👍','slug'=>'thumbs-up',             'name'=>'thumbs up',              'category'=>'symbols',           'group'=>'emotion'],
            ['character'=>'🙌','slug'=>'raising-hands',         'name'=>'raising hands',          'category'=>'symbols',           'group'=>'emotion'],
            // Nature
            ['character'=>'🌸','slug'=>'cherry-blossom',        'name'=>'cherry blossom',         'category'=>'animals and nature','group'=>'plant flower'],
            ['character'=>'🦁','slug'=>'lion',                  'name'=>'lion',                   'category'=>'animals and nature','group'=>'animal mammal'],
            ['character'=>'🐪','slug'=>'camel',                 'name'=>'camel',                  'category'=>'animals and nature','group'=>'animal mammal'],
            ['character'=>'🌵','slug'=>'cactus',                'name'=>'cactus',                 'category'=>'animals and nature','group'=>'plant other'],
            // Activities
            ['character'=>'🏄','slug'=>'surfer',                'name'=>'surfer',                 'category'=>'activities',        'group'=>'activities'],
            ['character'=>'🤿','slug'=>'diving-mask',           'name'=>'diving mask',            'category'=>'activities',        'group'=>'activities'],
            ['character'=>'📸','slug'=>'camera-flash',          'name'=>'camera flash',           'category'=>'objects',           'group'=>'objects'],
            ['character'=>'🎒','slug'=>'backpack',              'name'=>'backpack',               'category'=>'objects',           'group'=>'objects'],
            ['character'=>'🧭','slug'=>'compass',               'name'=>'compass',                'category'=>'objects',           'group'=>'objects'],
        ];
    }
}