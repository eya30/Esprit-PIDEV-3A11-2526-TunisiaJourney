/**
 * emoji-service.js
 * Client JS pour les emojis via votre backend Symfony,
 * qui lui-même appelle l'API EmojiHub (gratuite, sans clé).
 *
 * Endpoints backend utilisés :
 *   GET /api/emojis/travel?limit=N
 *   GET /api/emojis/search?q=QUERY&limit=N
 *   GET /api/emojis/trending?limit=N
 *   GET /api/emojis/category/{category}?limit=N
 *   GET /api/emojis/random?limit=N
 */
(function (global) {
    'use strict';

    // ─── Cache simple en mémoire ────────────────────────────────────────────
    var _cache = {};

    function cacheGet(key) { return _cache[key] || null; }
    function cacheSet(key, val) { _cache[key] = val; }

    // ─── Requête générique ──────────────────────────────────────────────────
    async function apiGet(path) {
        var cached = cacheGet(path);
        if (cached) return cached;

        var resp = await fetch(path, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!resp.ok) throw new Error('HTTP ' + resp.status);

        var data = await resp.json();
        if (data && data.success && Array.isArray(data.emojis)) {
            cacheSet(path, data.emojis);
            return data.emojis;
        }
        return [];
    }

    // ─── Service public ─────────────────────────────────────────────────────
    var EmojiService = {

        /**
         * Emojis liés au voyage (catégorie travel-and-places d'EmojiHub).
         * @param {number} limit
         * @returns {Promise<Array>}
         */
        getTravelEmojis: async function (limit) {
            limit = limit || 30;
            try {
                return await apiGet('/api/emojis/travel?limit=' + limit);
            } catch (e) {
                console.warn('EmojiService.getTravelEmojis:', e);
                return this._fallback();
            }
        },

        /**
         * Recherche d'emojis par mot-clé.
         * Si la requête est vide, retourne des emojis voyage.
         * @param {string} query
         * @param {number} limit
         * @returns {Promise<Array>}
         */
        searchEmojis: async function (query, limit) {
            limit = limit || 20;
            query = (query || '').trim();

            if (!query) {
                return this.getTravelEmojis(limit);
            }

            // 1. Essayer l'API backend
            try {
                var emojis = await apiGet(
                    '/api/emojis/search?q=' + encodeURIComponent(query) + '&limit=' + limit
                );
                if (emojis.length > 0) return emojis;
            } catch (e) {
                console.warn('EmojiService.searchEmojis API:', e);
            }

            // 2. Fallback local
            return this._searchLocal(query, limit);
        },

        /**
         * Emojis populaires (smileys + travel).
         * @param {number} limit
         * @returns {Promise<Array>}
         */
        getTrendingEmojis: async function (limit) {
            limit = limit || 30;
            try {
                return await apiGet('/api/emojis/trending?limit=' + limit);
            } catch (e) {
                console.warn('EmojiService.getTrendingEmojis:', e);
                return this._fallback();
            }
        },

        /**
         * Emojis par catégorie EmojiHub.
         * Catégories : smileys-and-people, animals-and-nature, food-and-drink,
         *              travel-and-places, activities, objects, symbols, flags
         * @param {string} category
         * @param {number} limit
         * @returns {Promise<Array>}
         */
        getEmojisByCategory: async function (category, limit) {
            limit = limit || 30;
            try {
                return await apiGet('/api/emojis/category/' + encodeURIComponent(category) + '?limit=' + limit);
            } catch (e) {
                console.warn('EmojiService.getEmojisByCategory:', e);
                return this._fallback();
            }
        },

        /**
         * Emojis aléatoires.
         * @param {number} limit
         * @returns {Promise<Array>}
         */
        getRandomEmojis: async function (limit) {
            limit = limit || 10;
            try {
                return await apiGet('/api/emojis/random?limit=' + limit);
            } catch (e) {
                console.warn('EmojiService.getRandomEmojis:', e);
                return this._fallback();
            }
        },

        /**
         * Vide le cache (utile après un "refresh").
         */
        clearCache: function () {
            _cache = {};
        },

        // ─── Privé ────────────────────────────────────────────────────────

        _fallback: function () {
            return this._allFallback().slice(0, 30);
        },

        _searchLocal: function (query, limit) {
            var q       = query.toLowerCase();
            var results = this._allFallback().filter(function (e) {
                var slug  = (e.slug  || '').toLowerCase();
                var name  = (e.name  || '').toLowerCase();
                var group = (e.group || '').toLowerCase();
                return slug.includes(q) || name.includes(q) || group.includes(q);
            });
            return results.slice(0, limit);
        },

        /**
         * Liste de secours complète (utilisée si l'API est indisponible).
         * Format identique à ce que retourne le backend.
         */
        _allFallback: function () {
            return [
                // ── Smileys ──────────────────────────────────────────────────
                { character: '😊', slug: 'smiling-face',          name: 'smiling face',         category: 'smileys and people', group: 'face positive' },
                { character: '😍', slug: 'heart-eyes',             name: 'heart eyes',            category: 'smileys and people', group: 'face positive' },
                { character: '😂', slug: 'tears-of-joy',           name: 'tears of joy',          category: 'smileys and people', group: 'face positive' },
                { character: '🤩', slug: 'star-struck',            name: 'star struck',           category: 'smileys and people', group: 'face positive' },
                { character: '😎', slug: 'cool-sunglasses',        name: 'cool sunglasses',       category: 'smileys and people', group: 'face positive' },
                { character: '🥰', slug: 'smiling-hearts',         name: 'smiling hearts',        category: 'smileys and people', group: 'face positive' },
                { character: '😄', slug: 'grinning-face',          name: 'grinning face',         category: 'smileys and people', group: 'face positive' },
                { character: '🤗', slug: 'hugging-face',           name: 'hugging face',          category: 'smileys and people', group: 'face positive' },
                { character: '😇', slug: 'angel-face',             name: 'angel face',            category: 'smileys and people', group: 'face positive' },
                { character: '🥳', slug: 'party-face',             name: 'party face',            category: 'smileys and people', group: 'face positive' },
                { character: '😜', slug: 'winking-tongue',         name: 'winking face tongue',   category: 'smileys and people', group: 'face positive' },
                { character: '🤭', slug: 'hand-over-mouth',        name: 'hand over mouth',       category: 'smileys and people', group: 'face positive' },
                { character: '😁', slug: 'beaming-face',           name: 'beaming face',          category: 'smileys and people', group: 'face positive' },
                { character: '🤔', slug: 'thinking-face',          name: 'thinking face',         category: 'smileys and people', group: 'face neutral' },
                // ── Travel ───────────────────────────────────────────────────
                { character: '✈️', slug: 'airplane',               name: 'airplane',              category: 'travel and places',  group: 'travel and places' },
                { character: '🌍', slug: 'globe-africa',           name: 'globe africa',          category: 'travel and places',  group: 'travel and places' },
                { character: '🏖️', slug: 'beach-umbrella',         name: 'beach umbrella',        category: 'travel and places',  group: 'travel and places' },
                { character: '🏝️', slug: 'desert-island',          name: 'desert island',         category: 'travel and places',  group: 'travel and places' },
                { character: '🏨', slug: 'hotel',                  name: 'hotel',                 category: 'travel and places',  group: 'travel and places' },
                { character: '🗺️', slug: 'world-map',              name: 'world map',             category: 'travel and places',  group: 'travel and places' },
                { character: '🧳', slug: 'luggage',                name: 'luggage',               category: 'travel and places',  group: 'travel and places' },
                { character: '🚗', slug: 'automobile',             name: 'automobile',            category: 'travel and places',  group: 'travel and places' },
                { character: '⛰️', slug: 'mountain',               name: 'mountain',              category: 'travel and places',  group: 'travel and places' },
                { character: '🗼', slug: 'eiffel-tower',           name: 'eiffel tower',          category: 'travel and places',  group: 'travel and places' },
                { character: '🌴', slug: 'palm-tree',              name: 'palm tree',             category: 'travel and places',  group: 'travel and places' },
                { character: '🏕️', slug: 'camping',                name: 'camping',               category: 'travel and places',  group: 'travel and places' },
                { character: '🚢', slug: 'ship',                   name: 'ship',                  category: 'travel and places',  group: 'travel and places' },
                { character: '🚂', slug: 'locomotive',             name: 'locomotive',            category: 'travel and places',  group: 'travel and places' },
                { character: '🌅', slug: 'sunrise',                name: 'sunrise',               category: 'travel and places',  group: 'travel and places' },
                { character: '🌊', slug: 'ocean-wave',             name: 'ocean wave',            category: 'travel and places',  group: 'travel and places' },
                { character: '🏔️', slug: 'snow-mountain',          name: 'snow mountain',         category: 'travel and places',  group: 'travel and places' },
                { character: '🌋', slug: 'volcano',                name: 'volcano',               category: 'travel and places',  group: 'travel and places' },
                { character: '🗽', slug: 'statue-of-liberty',      name: 'statue of liberty',     category: 'travel and places',  group: 'travel and places' },
                { character: '🎡', slug: 'ferris-wheel',           name: 'ferris wheel',          category: 'travel and places',  group: 'travel and places' },
                { character: '🌉', slug: 'bridge-at-night',        name: 'bridge at night',       category: 'travel and places',  group: 'travel and places' },
                { character: '🕌', slug: 'mosque',                 name: 'mosque',                category: 'travel and places',  group: 'travel and places' },
                { character: '🏟️', slug: 'stadium',                name: 'stadium',               category: 'travel and places',  group: 'travel and places' },
                // ── Food & Drink ──────────────────────────────────────────────
                { character: '🍕', slug: 'pizza',                  name: 'pizza',                 category: 'food and drink',     group: 'food prepared' },
                { character: '🍷', slug: 'wine-glass',             name: 'wine glass',            category: 'food and drink',     group: 'drink' },
                { character: '🍽️', slug: 'fork-knife-plate',       name: 'fork knife plate',      category: 'food and drink',     group: 'dishware' },
                { character: '🥗', slug: 'green-salad',            name: 'green salad',           category: 'food and drink',     group: 'food prepared' },
                { character: '🍰', slug: 'shortcake',              name: 'shortcake',             category: 'food and drink',     group: 'food sweet' },
                { character: '☕', slug: 'hot-beverage',           name: 'hot beverage',          category: 'food and drink',     group: 'drink' },
                { character: '🍦', slug: 'soft-ice-cream',         name: 'soft ice cream',        category: 'food and drink',     group: 'food sweet' },
                { character: '🥘', slug: 'shallow-pan-of-food',    name: 'shallow pan of food',   category: 'food and drink',     group: 'food prepared' },
                { character: '🍜', slug: 'steaming-bowl',          name: 'steaming bowl',         category: 'food and drink',     group: 'food asian' },
                { character: '🥐', slug: 'croissant',              name: 'croissant',             category: 'food and drink',     group: 'food prepared' },
                { character: '🍣', slug: 'sushi',                  name: 'sushi',                 category: 'food and drink',     group: 'food asian' },
                { character: '🥩', slug: 'cut-of-meat',            name: 'cut of meat',           category: 'food and drink',     group: 'food prepared' },
                // ── Activities ───────────────────────────────────────────────
                { character: '🏄', slug: 'surfer',                 name: 'surfer',                category: 'activities',         group: 'activities' },
                { character: '🤿', slug: 'diving-mask',            name: 'diving mask',           category: 'activities',         group: 'activities' },
                { character: '🎣', slug: 'fishing',                name: 'fishing',               category: 'activities',         group: 'activities' },
                { character: '🧗', slug: 'climbing',               name: 'climbing',              category: 'activities',         group: 'activities' },
                { character: '🚵', slug: 'mountain-biking',        name: 'mountain biking',       category: 'activities',         group: 'activities' },
                { character: '⛷️', slug: 'skiing',                 name: 'skiing',                category: 'activities',         group: 'activities' },
                { character: '🏊', slug: 'swimming',               name: 'swimming',              category: 'activities',         group: 'activities' },
                { character: '🧘', slug: 'yoga',                   name: 'yoga',                  category: 'activities',         group: 'activities' },
                // ── Objects ──────────────────────────────────────────────────
                { character: '📸', slug: 'camera-flash',           name: 'camera flash',          category: 'objects',            group: 'objects' },
                { character: '🎒', slug: 'backpack',               name: 'backpack',              category: 'objects',            group: 'objects' },
                { character: '🔭', slug: 'telescope',              name: 'telescope',             category: 'objects',            group: 'objects' },
                { character: '🧭', slug: 'compass',                name: 'compass',               category: 'objects',            group: 'objects' },
                { character: '🏷️', slug: 'label',                  name: 'label',                 category: 'objects',            group: 'objects' },
                { character: '📷', slug: 'camera',                 name: 'camera',                category: 'objects',            group: 'objects' },
                { character: '🗒️', slug: 'spiral-notepad',         name: 'spiral notepad',        category: 'objects',            group: 'objects' },
                // ── Symbols ──────────────────────────────────────────────────
                { character: '❤️', slug: 'red-heart',              name: 'red heart',             category: 'symbols',            group: 'emotion' },
                { character: '🔥', slug: 'fire',                   name: 'fire',                  category: 'symbols',            group: 'symbols' },
                { character: '💯', slug: 'hundred-points',         name: 'hundred points',        category: 'symbols',            group: 'symbols' },
                { character: '⭐', slug: 'star',                   name: 'star',                  category: 'symbols',            group: 'symbols' },
                { character: '🌟', slug: 'glowing-star',           name: 'glowing star',          category: 'symbols',            group: 'symbols' },
                { character: '✨', slug: 'sparkles',               name: 'sparkles',              category: 'symbols',            group: 'symbols' },
                { character: '💫', slug: 'dizzy-star',             name: 'dizzy star',            category: 'symbols',            group: 'symbols' },
                { character: '👍', slug: 'thumbs-up',              name: 'thumbs up',             category: 'symbols',            group: 'emotion' },
                { character: '🙌', slug: 'raising-hands',          name: 'raising hands',         category: 'symbols',            group: 'emotion' },
                // ── Nature ───────────────────────────────────────────────────
                { character: '🌸', slug: 'cherry-blossom',         name: 'cherry blossom',        category: 'animals and nature', group: 'plant flower' },
                { character: '🌺', slug: 'hibiscus',               name: 'hibiscus',              category: 'animals and nature', group: 'plant flower' },
                { character: '🦁', slug: 'lion',                   name: 'lion',                  category: 'animals and nature', group: 'animal mammal' },
                { character: '🐪', slug: 'camel',                  name: 'camel',                 category: 'animals and nature', group: 'animal mammal' },
                { character: '🦅', slug: 'eagle',                  name: 'eagle',                 category: 'animals and nature', group: 'animal bird' },
                { character: '🐠', slug: 'tropical-fish',          name: 'tropical fish',         category: 'animals and nature', group: 'animal marine' },
                { character: '🌵', slug: 'cactus',                 name: 'cactus',                category: 'animals and nature', group: 'plant other' },
                { character: '🌻', slug: 'sunflower',              name: 'sunflower',             category: 'animals and nature', group: 'plant flower' },
                { character: '🦋', slug: 'butterfly',              name: 'butterfly',             category: 'animals and nature', group: 'animal bug' },
                { character: '🐬', slug: 'dolphin',                name: 'dolphin',               category: 'animals and nature', group: 'animal marine' },
            ];
        }
    };

    global.EmojiService = EmojiService;

})(window);