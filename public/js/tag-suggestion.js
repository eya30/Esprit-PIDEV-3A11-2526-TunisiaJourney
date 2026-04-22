/**
 * tag-suggestion.js
 * Service de suggestion de tags via l'API AI Auto Tagging (backend Symfony)
 */
(function (global) {
    'use strict';

    var TagSuggestionService = {

        /**
         * Suggère des tags à partir de l'ID d'une publication (titre + description récupérés côté serveur)
         * Route : POST /api/tags/suggest/{idP}
         */
        suggest: async function (pubId, existingTags, maxTags) {
            maxTags = maxTags || 8;
            try {
                var resp = await fetch('/api/tags/suggest/' + pubId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        existingTags: existingTags || '',
                        maxTags: maxTags
                    }),
                });
                var data = await resp.json();
                return (data.success && Array.isArray(data.tags)) ? data.tags : [];
            } catch (e) {
                console.warn('TagSuggestionService.suggest error:', e);
                return [];
            }
        },

        /**
         * Suggère des tags à partir d'un texte libre (commentaire en cours de saisie)
         * Route : POST /api/tags/suggest  ← route simplifiée utilisée par le frontend
         */
        suggestFromText: async function (text, existingTags, maxTags) {
            maxTags = maxTags || 8;
            if (!text || text.trim().length < 10) return [];
            try {
                var resp = await fetch('/api/tags/suggest', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        text: text,
                        existingTags: existingTags || '',
                        maxTags: maxTags
                    }),
                });
                var data = await resp.json();
                return (data.success && Array.isArray(data.tags)) ? data.tags : [];
            } catch (e) {
                console.warn('TagSuggestionService.suggestFromText error:', e);
                return [];
            }
        },
    };

    global.TagSuggestionService = TagSuggestionService;

})(window);