// public/js/giphy-service.js

class GiphyService {
    constructor() {
        this.apiKey = 'daT405ErJP0TDn1knfh8iXMKj5rvn26t';
        this.baseUrl = 'https://api.giphy.com/v1/gifs';
        this.isLoading = false;
        this.currentPage = 0;
        this.currentQuery = '';
        this.lastResults = [];
    }

    async search(query, limit = 20, offset = 0) {
        if (!query || query.trim() === '') {
            return await this.getTrending(limit, offset);
        }
        this.currentQuery = query;
        this.currentPage = Math.floor(offset / limit);
        const url = `${this.baseUrl}/search?api_key=${this.apiKey}&q=${encodeURIComponent(query)}&limit=${limit}&offset=${offset}&rating=pg-13&lang=fr`;
        try {
            this.isLoading = true;
            const response = await fetch(url);
            const data = await response.json();
            this.isLoading = false;
            this.lastResults = data.data || [];
            return { success: true, gifs: this.lastResults, pagination: data.pagination };
        } catch (error) {
            this.isLoading = false;
            return { success: false, error: error.message, gifs: [] };
        }
    }

    async getTrending(limit = 20, offset = 0) {
        this.currentQuery = '';
        const url = `${this.baseUrl}/trending?api_key=${this.apiKey}&limit=${limit}&offset=${offset}&rating=pg-13`;
        try {
            this.isLoading = true;
            const response = await fetch(url);
            const data = await response.json();
            this.isLoading = false;
            this.lastResults = data.data || [];
            return { success: true, gifs: this.lastResults, pagination: data.pagination };
        } catch (error) {
            this.isLoading = false;
            return { success: false, error: error.message, gifs: [] };
        }
    }

    async loadMore() {
        const offset = (this.currentPage + 1) * 20;
        if (this.currentQuery) {
            return await this.search(this.currentQuery, 20, offset);
        } else {
            return await this.getTrending(20, offset);
        }
    }

    getGifUrl(gif, size = 'fixed_height') {
        if (gif.images && gif.images[size]) {
            return gif.images[size].url;
        }
        return gif.images?.fixed_height?.url || '';
    }

    isConfigured() {
        return this.apiKey && this.apiKey !== 'YOUR_GIPHY_API_KEY_HERE';
    }
}

window.GiphyService = new GiphyService();

// ════════════════════════════════════════════════════════════════════
// ✅ FONCTION CORRIGÉE : renderCommentText 
// Convertit [[GIF:URL]] en VRAIE image HTML
// ════════════════════════════════════════════════════════════════════
window.renderCommentText = function(rawText) {
    if (!rawText || typeof rawText !== 'string') {
        return '';
    }

    // Fonction d'échappement HTML sécurisé
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Découpage du texte par les marqueurs GIF
    var gifRegex = /\[\[GIF:([^\]]+)\]\]/gi;
    var parts = [];
    var lastIndex = 0;
    var match;

    while ((match = gifRegex.exec(rawText)) !== null) {
        // Texte avant le GIF
        if (match.index > lastIndex) {
            var textBefore = rawText.substring(lastIndex, match.index);
            if (textBefore.trim()) {
                parts.push({
                    type: 'text',
                    content: escapeHtml(textBefore).replace(/\n/g, '<br>')
                });
            }
        }
        
        // URL du GIF
        var gifUrl = match[1].trim();
        parts.push({
            type: 'gif',
            url: escapeHtml(gifUrl)
        });
        
        lastIndex = match.index + match[0].length;
    }

    // Texte restant
    if (lastIndex < rawText.length) {
        var remaining = rawText.substring(lastIndex);
        if (remaining.trim()) {
            parts.push({
                type: 'text',
                content: escapeHtml(remaining).replace(/\n/g, '<br>')
            });
        }
    }

    // Si pas de GIF, retourner texte simple
    if (parts.length === 0) {
        return escapeHtml(rawText).replace(/\n/g, '<br>');
    }

    // Construire le HTML final avec VRAIES images GIF
    var html = '';
    for (var i = 0; i < parts.length; i++) {
        var part = parts[i];
        if (part.type === 'text') {
            html += '<div class="comment-text-line">' + part.content + '</div>';
        } else if (part.type === 'gif') {
            html += '<div class="comment-gif-container">' +
                '<img src="' + part.url + '" ' +
                'class="comment-gif-image" ' +
                'alt="GIF animé" ' +
                'loading="lazy" ' +
                'onerror="this.parentElement.innerHTML=\'<div class=\\\'comment-gif-error\\\'><i class=\\\'fas fa-exclamation-triangle\\\'></i> GIF non disponible</div>\'" ' +
                '>' +
                '<span class="comment-gif-badge">GIF</span>' +
                '</div>';
        }
    }

    return html;
};

// Fonction helper pour afficher un commentaire dans un élément
window.displayCommentInElement = function(elementId, rawText) {
    var element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = window.renderCommentText(rawText);
    }
};

console.log('✅ GIPHY Service chargé et prêt!');