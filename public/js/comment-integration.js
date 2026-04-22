// public/js/comment-integration.js
// Script d'intégration pour le système de commentaires avec GIFs

(function() {
    'use strict';

    // Fonction pour insérer un GIF dans le textarea
    window.insertGifToComment = function(gif) {
        var textarea = document.getElementById('modalCommentText');
        if (!textarea) {
            console.error('Textarea non trouvé');
            return;
        }
        
        var gifUrl = window.GiphyService.getGifUrl(gif, 'fixed_height');
        if (!gifUrl) {
            showToast('❌ Impossible de récupérer le GIF', 'error');
            return;
        }
        
        var currentValue = textarea.value;
        var gifMarkup = '[[GIF:' + gifUrl + ']]';
        var newValue = currentValue.trim() 
            ? currentValue + '\n\n' + gifMarkup 
            : gifMarkup;
        
        textarea.value = newValue;
        textarea.focus();
        
        // Prévisualisation optionnelle
        var previewDiv = document.getElementById('comment-preview');
        if (previewDiv) {
            previewDiv.innerHTML = window.renderCommentText(newValue);
        }
        
        closeGifPicker();
        showToast('✅ GIF ajouté !', 'success');
    };

    // Fonction pour prévisualiser le commentaire
    window.previewComment = function() {
        var textarea = document.getElementById('modalCommentText');
        var previewDiv = document.getElementById('comment-preview');
        
        if (textarea && previewDiv) {
            previewDiv.innerHTML = window.renderCommentText(textarea.value);
            previewDiv.style.display = 'block';
        }
    };

    // Fonction pour charger les commentaires avec rendu GIF
    window.loadCommentsWithGifs = async function(pubId) {
        var container = document.getElementById('modalCommentsList');
        if (!container) return;
        
        container.innerHTML = '<div class="loading-comments"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
        
        try {
            var response = await fetch('/commentaire/publication/' + pubId + '/comments', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            var data = await response.json();
            
            if (data.success && data.comments && data.comments.length > 0) {
                var commentsHtml = '';
                for (var i = 0; i < data.comments.length; i++) {
                    var comment = data.comments[i];
                    // Utiliser le renderer complet
                    if (window.CommentRenderer && window.CommentRenderer.renderFull) {
                        commentsHtml += window.CommentRenderer.renderFull(comment);
                    } else {
                        // Fallback
                        commentsHtml += renderCommentFallback(comment);
                    }
                }
                container.innerHTML = commentsHtml;
                
                // Mettre à jour le badge
                var badge = document.getElementById('commentsCountBadge');
                if (badge) {
                    badge.textContent = data.total + ' commentaire' + (data.total > 1 ? 's' : '');
                }
            } else {
                container.innerHTML = '<div class="empty-comments">' +
                    '<i class="fas fa-comment-dots"></i>' +
                    '<p>Soyez le premier à commenter !</p>' +
                    '</div>';
            }
        } catch (error) {
            console.error('Erreur:', error);
            container.innerHTML = '<div class="error-comments">' +
                '<i class="fas fa-exclamation-circle"></i>' +
                '<p>Erreur lors du chargement</p>' +
                '</div>';
        }
    };

    // Fallback si CommentRenderer n'est pas disponible
    function renderCommentFallback(comment) {
        var content = window.renderCommentText ? 
            window.renderCommentText(comment.description) : 
            escapeHtml(comment.description).replace(/\n/g, '<br>');
        
        return '<div class="comment-item">' +
            '<div class="comment-header">' +
                '<div class="comment-avatar">V</div>' +
                '<div class="comment-meta">' +
                    '<span class="comment-author">Voyageur</span>' +
                    '<span class="comment-date">' + escapeHtml(comment.date) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="comment-content">' + content + '</div>' +
        '</div>';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showToast(msg, type) {
        var toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.background = type === 'error' ? '#c0392b' : '#27ae60';
        toast.innerHTML = msg;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }

    function closeGifPicker() {
        var modal = document.getElementById('gifPickerModal');
        if (modal) modal.classList.remove('open');
    }

    console.log('✅ Comment integration chargée');
})();