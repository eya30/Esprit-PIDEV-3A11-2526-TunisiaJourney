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
     */
    private const TRAVEL_GROUPS = [
        'travel-and-places',
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
     *
     * @return array<int, array<string, string>>
     */
    public function getTravelEmojis(int $limit = 30): array
    {
        $emojis = $this->fetchCategory('travel-and-places', $limit);

        if (count($emojis) < $limit) {
            $extra  = $this->fetchRandom(max(1, $limit - count($emojis)));
            $emojis = array_merge($emojis, $extra);
        }

        if (empty($emojis)) {
            return $this->getFallbackEmojis();
        }

        return array_slice($this->dedup($emojis), 0, $limit);
    }

    /**
     * Recherche des emojis par nom (via /api/all + filtre local).
     *
     * @return array<int, array<string, string>>
     */
    public function searchEmojis(string $query, int $limit = 20): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return $this->getTravelEmojis($limit);
        }

        $category = $this->mapQueryToCategory($query);
        if ($category !== null) {
            $emojis = $this->fetchCategory($category, $limit * 2);
            if (!empty($emojis)) {
                return array_slice($emojis, 0, $limit);
            }
        }

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
     *
     * @return array<int, array<string, string>>
     */
    public function getTrendingEmojis(int $limit = 30): array
    {
        $smiley = $this->fetchCategory('smileys-and-people', 15);
        $travel = $this->fetchCategory('travel-and-places', 15);
        $all    = array_merge($smiley, $travel);

        if (empty($all)) {
            return $this->getFallbackEmojis();
        }

        shuffle($all);
        return array_slice($this->dedup($all), 0, $limit);
    }

    /**
     * Retourne les emojis d'une catégorie EmojiHub.
     *
     * @return array<int, array<string, string>>
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
     *
     * @return array<int|string, mixed>
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
     *
     * @return array<int|string, mixed>
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
     * GET /api/random — Retourne un seul emoji aléatoire.
     *
     * @return array<string, string>|null
     */
    private function fetchOneRandom(): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/random', [
                'timeout' => self::TIMEOUT,
            ]);
            if ($response->getStatusCode() === 200) {
                // Fix ligne 200 : toArray() retourne toujours array, is_array() inutile
                return $this->normalize($response->toArray(false));
            }
        } catch (\Throwable $e) {
            $this->logger->warning('EmojiHub random error: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Appelle /api/random plusieurs fois pour obtenir $count emojis.
     *
     * @return array<int, array<string, string>>
     */
    private function fetchRandom(int $count): array
    {
        $results  = [];
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
     *
     * @return array<int, array<string, string>>
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

            // Fix ligne 241 : toArray() retourne toujours array, is_array() supprimé
            $data = $response->toArray(false);
            if (empty($data)) {
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
     *
     * @return array<int, array<string, string>>
     */
    // @phpstan-ignore-next-line method.unused
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

            // Fix ligne 275 : toArray() retourne toujours array, is_array() supprimé
            $data = $response->toArray(false);
            if (empty($data)) {
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
     * GET /api/all — Retourne tous les emojis (1791 objets).
     *
     * @return array<int, array<string, string>>
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

            // Fix ligne 309 : toArray() retourne toujours array, is_array() supprimé
            $data       = $response->toArray(false);
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
     * @param mixed $raw
     * @return array<string, string>|null
     */
    private function normalize(mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        $htmlCodes = $raw['htmlCode'] ?? [];
        if (!is_array($htmlCodes) || empty($htmlCodes)) {
            return null;
        }

        $character = '';
        foreach ($htmlCodes as $code) {
            $decoded = html_entity_decode((string) $code, ENT_HTML5, 'UTF-8');
            if ($decoded === '') {
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

    private function mapQueryToCategory(string $query): ?string
    {
        $map = [
            'voyage'     => 'travel-and-places',
            'travel'     => 'travel-and-places',
            'avion'      => 'travel-and-places',
            'plage'      => 'travel-and-places',
            'montagne'   => 'travel-and-places',
            'hotel'      => 'travel-and-places',
            'food'       => 'food-and-drink',
            'nourriture' => 'food-and-drink',
            'manger'     => 'food-and-drink',
            'animal'     => 'animals-and-nature',
            'nature'     => 'animals-and-nature',
            'sport'      => 'activities',
            'activite'   => 'activities',
            'smiley'     => 'smileys-and-people',
            'visage'     => 'smileys-and-people',
            'flag'       => 'flags',
            'drapeau'    => 'flags',
            'symbole'    => 'symbols',
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
     *
     * @param array<int, array<string, string>> $emojis
     * @return array<int, array<string, string>>
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
     *
     * @return array<int, array<string, string>>
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
     *
     * @return array<int, array<string, string>>
     */
    public function getFallbackEmojis(): array
    {
        return array_slice($this->getExtendedFallbackEmojis(), 0, 30);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getExtendedFallbackEmojis(): array
    {
        return [
            // Smileys
            ['character' => '😊', 'slug' => 'smiling-face',   'name' => 'smiling face',   'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '😍', 'slug' => 'heart-eyes',     'name' => 'heart eyes',     'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '😂', 'slug' => 'tears-of-joy',   'name' => 'tears of joy',   'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '🤩', 'slug' => 'star-struck',    'name' => 'star struck',    'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '😎', 'slug' => 'cool-sunglasses','name' => 'cool sunglasses','category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '🥰', 'slug' => 'smiling-hearts', 'name' => 'smiling hearts', 'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '😄', 'slug' => 'grinning-face',  'name' => 'grinning face',  'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '🤗', 'slug' => 'hugging-face',   'name' => 'hugging face',   'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '😇', 'slug' => 'angel-face',     'name' => 'angel face',     'category' => 'smileys and people', 'group' => 'face positive'],
            ['character' => '🥳', 'slug' => 'party-face',     'name' => 'party face',     'category' => 'smileys and people', 'group' => 'face positive'],
            // Travel
            ['character' => '✈️', 'slug' => 'airplane',       'name' => 'airplane',       'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🌍', 'slug' => 'globe-africa',   'name' => 'globe africa',   'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🏖️', 'slug' => 'beach-umbrella', 'name' => 'beach umbrella', 'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🏝️', 'slug' => 'desert-island',  'name' => 'desert island',  'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🏨', 'slug' => 'hotel',          'name' => 'hotel',          'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🗺️', 'slug' => 'world-map',      'name' => 'world map',      'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🧳', 'slug' => 'luggage',        'name' => 'luggage',        'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🚗', 'slug' => 'automobile',     'name' => 'automobile',     'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '⛰️', 'slug' => 'mountain',       'name' => 'mountain',       'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🗼', 'slug' => 'eiffel-tower',   'name' => 'eiffel tower',   'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🌴', 'slug' => 'palm-tree',      'name' => 'palm tree',      'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🏕️', 'slug' => 'camping',        'name' => 'camping',        'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🚢', 'slug' => 'ship',           'name' => 'ship',           'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🌅', 'slug' => 'sunrise',        'name' => 'sunrise',        'category' => 'travel and places',  'group' => 'travel and places'],
            ['character' => '🌊', 'slug' => 'ocean-wave',     'name' => 'ocean wave',     'category' => 'travel and places',  'group' => 'travel and places'],
            // Food
            ['character' => '🍕', 'slug' => 'pizza',          'name' => 'pizza',          'category' => 'food and drink',     'group' => 'food prepared'],
            ['character' => '🍷', 'slug' => 'wine-glass',     'name' => 'wine glass',     'category' => 'food and drink',     'group' => 'drink'],
            ['character' => '🥗', 'slug' => 'green-salad',    'name' => 'green salad',    'category' => 'food and drink',     'group' => 'food prepared'],
            ['character' => '🍰', 'slug' => 'shortcake',      'name' => 'shortcake',      'category' => 'food and drink',     'group' => 'food sweet'],
            ['character' => '☕', 'slug' => 'hot-beverage',   'name' => 'hot beverage',   'category' => 'food and drink',     'group' => 'drink'],
            // Symbols
            ['character' => '❤️', 'slug' => 'red-heart',      'name' => 'red heart',      'category' => 'symbols',            'group' => 'emotion'],
            ['character' => '🔥', 'slug' => 'fire',           'name' => 'fire',           'category' => 'symbols',            'group' => 'symbols'],
            ['character' => '💯', 'slug' => 'hundred-points', 'name' => 'hundred points', 'category' => 'symbols',            'group' => 'symbols'],
            ['character' => '⭐', 'slug' => 'star',           'name' => 'star',           'category' => 'symbols',            'group' => 'symbols'],
            ['character' => '✨', 'slug' => 'sparkles',       'name' => 'sparkles',       'category' => 'symbols',            'group' => 'symbols'],
            ['character' => '👍', 'slug' => 'thumbs-up',      'name' => 'thumbs up',      'category' => 'symbols',            'group' => 'emotion'],
            ['character' => '🙌', 'slug' => 'raising-hands',  'name' => 'raising hands',  'category' => 'symbols',            'group' => 'emotion'],
            // Nature
            ['character' => '🌸', 'slug' => 'cherry-blossom', 'name' => 'cherry blossom', 'category' => 'animals and nature', 'group' => 'plant flower'],
            ['character' => '🦁', 'slug' => 'lion',           'name' => 'lion',           'category' => 'animals and nature', 'group' => 'animal mammal'],
            ['character' => '🐪', 'slug' => 'camel',          'name' => 'camel',          'category' => 'animals and nature', 'group' => 'animal mammal'],
            ['character' => '🌵', 'slug' => 'cactus',         'name' => 'cactus',         'category' => 'animals and nature', 'group' => 'plant other'],
            // Activities
            ['character' => '🏄', 'slug' => 'surfer',         'name' => 'surfer',         'category' => 'activities',         'group' => 'activities'],
            ['character' => '🤿', 'slug' => 'diving-mask',    'name' => 'diving mask',    'category' => 'activities',         'group' => 'activities'],
            ['character' => '📸', 'slug' => 'camera-flash',   'name' => 'camera flash',   'category' => 'objects',            'group' => 'objects'],
            ['character' => '🎒', 'slug' => 'backpack',       'name' => 'backpack',       'category' => 'objects',            'group' => 'objects'],
            ['character' => '🧭', 'slug' => 'compass',        'name' => 'compass',        'category' => 'objects',            'group' => 'objects'],
        ];
    }
}
