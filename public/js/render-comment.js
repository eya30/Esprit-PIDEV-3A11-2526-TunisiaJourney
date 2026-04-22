// public/js/render-comment.js

/**
 * Service complet de rendu des commentaires avec support GIF
 * Version finale corrigée - Affiche les images réelles
 */

(function(window) {
    'use strict';

    // Cache des likes
    var commentLikes = {};

    /**
     * Échappement HTML sécurisé
     */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Convertit le texte d'un commentaire en HTML avec VRAIES images GIF
     * Format supporté: [[GIF:URL]]
     */
    function renderCommentText(rawText) {
        if (!rawText || typeof rawText !== 'string') {
            return '';
        }

        // Regex pour capturer les GIFs
        var gifRegex = /\[\[GIF:([^\]]+)\]\]/gi;
        var parts = [];
        var lastIndex = 0;
        var match;

        // Découper le texte en parties (texte/GIF)
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
            
            // Ajouter le GIF (URL brute)
            var gifUrl = match[1].trim();
            if (gifUrl) {
                parts.push({
                    type: 'gif',
                    url: gifUrl
                });
            }
            
            lastIndex = match.index + match[0].length;
        }

        // Texte après le dernier GIF
        if (lastIndex < rawText.length) {
            var remaining = rawText.substring(lastIndex);
            if (remaining.trim()) {
                parts.push({
                    type: 'text',
                    content: escapeHtml(remaining).replace(/\n/g, '<br>')
                });
            }
        }

        // Si aucun GIF, retourner texte simple
        if (parts.length === 0) {
            return '<div class="comment-text-line">' + escapeHtml(rawText).replace(/\n/g, '<br>') + '</div>';
        }

        // Construire le HTML final
        var html = '';
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];
            if (part.type === 'text') {
                html += '<div class="comment-text-part">' + part.content + '</div>';
            } else if (part.type === 'gif') {
                html += renderGifElement(part.url);
            }
        }

        return html;
    }

    /**
     * Rendre un élément GIF avec gestion d'erreur
     */
    function renderGifElement(gifUrl) {
        if (!gifUrl) return '';
        
        // Vérifier URL valide
        if (!gifUrl.startsWith('http://') && !gifUrl.startsWith('https://')) {
            return '<div class="comment-gif-error">' +
                '<i class="fas fa-exclamation-triangle"></i>' +
                '<span>URL GIF invalide</span>' +
                '</div>';
        }

        var gifId = 'gif_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
        
        return '<div class="comment-gif-wrapper" data-gif-id="' + gifId + '">' +
            '<img ' +
            'src="' + escapeHtml(gifUrl) + '" ' +
            'class="comment-gif-image" ' +
            'alt="GIF animé du commentaire" ' +
            'loading="lazy" ' +
            'onerror="this.onerror=null; this.parentElement.innerHTML=\'<div class=\\\'comment-gif-error\\\'><i class=\\\'fas fa-exclamation-triangle\\\'></i> ❌ Impossible de charger le GIF</div>\'" ' +
            '>' +
            '<span class="comment-gif-badge">GIF</span>' +
            '</div>';
    }

    /**
     * Rendre un commentaire complet
     */
    function renderFullComment(comment) {
        // Initialiser les likes
        if (!commentLikes[comment.id]) {
            commentLikes[comment.id] = {
                liked: false,
                count: comment.likes || 0
            };
        }
        
        var ld = commentLikes[comment.id];
        var parsedContent = renderCommentText(comment.description);
        
        return '<div class="comment-item" id="comment-' + comment.id + '" data-comment-id="' + comment.id + '">' +
            '<div class="comment-header">' +
                '<div class="comment-avatar">V</div>' +
                '<div class="comment-meta">' +
                    '<span class="comment-author">Voyageur</span>' +
                    '<span class="comment-date">' + escapeHtml(comment.date) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="comment-content" id="comment-content-' + comment.id + '">' +
                parsedContent +
            '</div>' +
            '<div class="comment-actions">' +
                '<button class="comment-like-btn" onclick="likeComment(' + comment.id + ')">' +
                    '<span class="like-icon">' + (ld.liked ? '❤️' : '🤍') + '</span>' +
                    '<span class="like-count">' + ld.count + '</span>' +
                '</button>' +
                '<button class="comment-reply-btn" onclick="toggleReplyForm(' + comment.id + ')">' +
                    '<i class="fas fa-reply"></i> Répondre' +
                '</button>' +
                '<button class="comment-report-btn" onclick="openReportModal(' + comment.id + ')">' +
                    '<i class="fas fa-flag"></i> Signaler' +
                '</button>' +
                renderLanguageSelector(comment.id) +
            '</div>' +
            '<div class="reply-form-container" id="reply-form-' + comment.id + '" style="display:none;">' +
                '<textarea class="reply-textarea" id="reply-text-' + comment.id + '" rows="2" placeholder="Votre réponse..."></textarea>' +
                '<div class="reply-actions">' +
                    '<button class="reply-submit-btn" onclick="submitReply(' + comment.id + ')">Envoyer</button>' +
                    '<button class="reply-cancel-btn" onclick="toggleReplyForm(' + comment.id + ')">Annuler</button>' +
                '</div>' +
            '</div>' +
            '<div class="comment-translate-bar" id="translate-bar-' + comment.id + '">' +
                '<span id="translate-preview-' + comment.id + '"></span>' +
                '<button class="translate-replace-btn" onclick="replaceCommentTranslation(' + comment.id + ')">Remplacer</button>' +
                '<button class="translate-cancel-btn" onclick="cancelCommentTranslation(' + comment.id + ')">Annuler</button>' +
            '</div>' +
        '</div>';
    }

    /**
     * Rendre le sélecteur de langue
     */
    function renderLanguageSelector(commentId) {
        return '<div class="comment-language-wrapper">' +
            '<select class="comment-language-select" id="lang-select-' + commentId + '">' +
                '<option value="">🌐 Langue</option>' +
                '<option value="fr">Français</option>' +
                '<option value="en">English</option>' +
                '<option value="ar">العربية</option>' +
                '<option value="es">Español</option>' +
                '<option value="de">Deutsch</option>' +
                '<option value="it">Italiano</option>' +
                '<option value="pt">Português</option>' +
            '</select>' +
            '<button class="comment-translate-btn" onclick="translateComment(' + commentId + ')">' +
                '<i class="fas fa-language"></i> Traduire' +
            '</button>' +
        '</div>';
    }

    /**
     * Mettre à jour le contenu d'un commentaire
     */
    function updateCommentContent(commentId, newText) {
        var contentDiv = document.getElementById('comment-content-' + commentId);
        if (contentDiv) {
            contentDiv.innerHTML = renderCommentText(newText);
        }
    }

    /**
     * Prévisualiser un commentaire avant envoi
     */
    function previewComment(rawText) {
        return renderCommentText(rawText);
    }

    // Exporter l'API publique
    window.CommentRenderer = {
        renderText: renderCommentText,
        renderFull: renderFullComment,
        renderGif: renderGifElement,
        updateContent: updateCommentContent,
        preview: previewComment,
        escapeHtml: escapeHtml
    };

    // Alias global pour compatibilité
    window.renderCommentText = renderCommentText;

})(window);

console.log('✅ Comment Renderer chargé!');