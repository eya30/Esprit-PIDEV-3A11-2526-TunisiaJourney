// public/js/reactions-service.js
// ════════════════════════════════════════════════════════════════════════════
// ✅ REACTIONS SERVICE — Système de réactions style Facebook
//    - 6 réactions : J'aime, J'adore, Haha, Wouah, Triste, Grrr
//    - Popup animé au survol du bouton J'aime
//    - Compteurs par type de réaction avec tooltip
//    - Persistance en localStorage (et envoi API si configuré)
//    - Support mobile (tap long = popup)
//    - Intégration complète dans forum_public.html.twig
// ════════════════════════════════════════════════════════════════════════════

const REACTIONS = {
    like:    { emoji: '👍', label: 'J\'aime',  color: '#1877F2', gradient: 'linear-gradient(135deg,#1877F2,#42A5F5)' },
    love:    { emoji: '❤️',  label: 'J\'adore', color: '#F33E58', gradient: 'linear-gradient(135deg,#F33E58,#FF6584)' },
    haha:    { emoji: '😂', label: 'Haha',     color: '#F7B928', gradient: 'linear-gradient(135deg,#F7B928,#FDD835)' },
    wow:     { emoji: '😮', label: 'Wouah',    color: '#F7B928', gradient: 'linear-gradient(135deg,#F7B928,#FF8F00)' },
    sad:     { emoji: '😢', label: 'Triste',   color: '#F7B928', gradient: 'linear-gradient(135deg,#F7B928,#64B5F6)' },
    angry:   { emoji: '😡', label: 'Grrr',     color: '#E9710F', gradient: 'linear-gradient(135deg,#E9710F,#FF5722)' },
};

// ─── CSS injecté dynamiquement ────────────────────────────────────────────────

(function injectReactionsCSS() {
    if (document.getElementById('reactions-css')) return;
    const style = document.createElement('style');
    style.id = 'reactions-css';
    style.textContent = `
/* ══════════════════════════════════════════════
   REACTIONS FACEBOOK — Styles complets
══════════════════════════════════════════════ */

/* Conteneur principal du bouton réaction */
.fb-reaction-wrap {
    position: relative;
    display: inline-block;
}

/* Bouton principal "J'aime" */
.btn-fb-react {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 16px;
    border-radius: 22px;
    border: 1.5px solid #E8DFD1;
    background: white;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .25s ease;
    color: #65676B;
    user-select: none;
    -webkit-user-select: none;
    white-space: nowrap;
    min-width: 80px;
    justify-content: center;
}

.btn-fb-react:hover {
    background: #F2F2F2;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

/* État actif — la couleur change selon la réaction choisie */
.btn-fb-react.reacted {
    border-color: transparent;
    color: white;
}
.btn-fb-react.reacted:hover {
    opacity: .9;
    filter: brightness(1.05);
}

/* Emoji du bouton principal */
.btn-fb-react .react-emoji {
    font-size: 1.1rem;
    transition: transform .3s cubic-bezier(.2,.9,.4,1.3);
    display: inline-block;
}
.btn-fb-react:hover .react-emoji {
    transform: scale(1.25) rotate(-5deg);
}
.btn-fb-react.reacted .react-emoji {
    transform: scale(1.15);
    animation: reactBounce .4s cubic-bezier(.2,.9,.4,1.3);
}
@keyframes reactBounce {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.4) rotate(8deg); }
    100% { transform: scale(1.15); }
}

/* ── POPUP des réactions ── */
.reactions-popup {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%) scale(0.7) translateY(10px);
    background: white;
    border-radius: 60px;
    box-shadow: 0 8px 32px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.1);
    padding: 10px 16px;
    display: flex;
    gap: 6px;
    opacity: 0;
    pointer-events: none;
    transition: all .28s cubic-bezier(.2,.9,.4,1.2);
    z-index: 9999;
    border: 1px solid rgba(0,0,0,.06);
    white-space: nowrap;
}

/* Flèche de la popup */
.reactions-popup::after {
    content: '';
    position: absolute;
    bottom: -7px;
    left: 50%;
    transform: translateX(-50%);
    width: 14px;
    height: 14px;
    background: white;
    border-right: 1px solid rgba(0,0,0,.06);
    border-bottom: 1px solid rgba(0,0,0,.06);
    rotate: 45deg;
}

/* Popup visible */
.reactions-popup.visible {
    opacity: 1;
    pointer-events: all;
    transform: translateX(-50%) scale(1) translateY(0);
}

/* Bouton réaction individuel dans la popup */
.reaction-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    cursor: pointer;
    padding: 6px 4px;
    border-radius: 12px;
    transition: all .2s ease;
    position: relative;
}
.reaction-option:hover {
    background: rgba(0,0,0,.04);
    transform: scale(1.25) translateY(-6px);
}
.reaction-option .r-emoji {
    font-size: 1.9rem;
    line-height: 1;
    display: block;
    transition: transform .2s cubic-bezier(.2,.9,.4,1.3);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,.15));
}
.reaction-option:hover .r-emoji {
    transform: scale(1.1);
    filter: drop-shadow(0 4px 8px rgba(0,0,0,.2));
}
.reaction-option .r-label {
    font-size: .62rem;
    font-weight: 700;
    white-space: nowrap;
    color: #444;
    opacity: 0;
    transition: opacity .15s ease;
    position: absolute;
    bottom: -18px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,.75);
    color: white;
    padding: 2px 6px;
    border-radius: 6px;
    pointer-events: none;
}
.reaction-option:hover .r-label {
    opacity: 1;
}

/* Animation d'entrée des emojis dans la popup */
.reactions-popup .reaction-option:nth-child(1) { transition-delay: 0ms; }
.reactions-popup .reaction-option:nth-child(2) { transition-delay: 30ms; }
.reactions-popup .reaction-option:nth-child(3) { transition-delay: 60ms; }
.reactions-popup .reaction-option:nth-child(4) { transition-delay: 90ms; }
.reactions-popup .reaction-option:nth-child(5) { transition-delay: 120ms; }
.reactions-popup .reaction-option:nth-child(6) { transition-delay: 150ms; }

/* ── Barre de compteurs de réactions ── */
.reactions-summary-bar {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: .78rem;
    color: #65676B;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 20px;
    transition: background .2s;
    user-select: none;
}
.reactions-summary-bar:hover {
    background: #F2F2F2;
}
.reactions-summary-bar .react-pill {
    display: inline-flex;
    align-items: center;
    gap: 2px;
}
.reactions-summary-bar .react-pill .pill-emoji {
    font-size: .9rem;
    line-height: 1;
}
.reactions-summary-bar .react-total {
    font-weight: 600;
    margin-left: 2px;
}

/* ── Tooltip détaillé au hover du compteur ── */
.reactions-detail-tooltip {
    position: absolute;
    background: rgba(0,0,0,.85);
    color: white;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: .75rem;
    white-space: nowrap;
    pointer-events: none;
    z-index: 9998;
    opacity: 0;
    transition: opacity .2s ease;
    min-width: 130px;
    box-shadow: 0 4px 16px rgba(0,0,0,.25);
}
.reactions-detail-tooltip.show {
    opacity: 1;
}
.reactions-detail-tooltip .tooltip-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 3px 0;
}
.reactions-detail-tooltip .tooltip-row span:last-child {
    margin-left: auto;
    font-weight: 700;
    color: #FFD700;
}

/* ── Notification flottante (like animé) ── */
.reaction-float-notif {
    position: fixed;
    font-size: 2rem;
    pointer-events: none;
    z-index: 99999;
    animation: floatUp 1.2s ease-out forwards;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,.2));
}
@keyframes floatUp {
    0%   { opacity: 1; transform: translateY(0) scale(1); }
    50%  { opacity: 1; transform: translateY(-30px) scale(1.3); }
    100% { opacity: 0; transform: translateY(-60px) scale(0.8); }
}

/* ── Adaptation mobile ── */
@media (max-width: 640px) {
    .reactions-popup {
        padding: 8px 10px;
        gap: 4px;
        left: 0;
        transform: translateX(0) scale(0.7) translateY(10px);
    }
    .reactions-popup.visible {
        transform: translateX(0) scale(1) translateY(0);
    }
    .reactions-popup::after {
        left: 20%;
    }
    .reaction-option .r-emoji {
        font-size: 1.6rem;
    }
    .r-label { display: none !important; }
}
    `;
    document.head.appendChild(style);
})();

// ─── Classe principale ReactionSystem ────────────────────────────────────────

class ReactionSystem {
    constructor() {
        this.userReactions  = this._loadFromStorage();
        this.reactionCounts = {};
        this._openPopup     = null;
        this._hoverTimer    = null;
        this._closeTimer    = null;
        this._longPressTimer = null;
    }

    // ── Persistance localStorage ─────────────────────────────────────────────

    _loadFromStorage() {
        try {
            return JSON.parse(localStorage.getItem('tj_reactions') || '{}');
        } catch { return {}; }
    }

    _saveToStorage() {
        try {
            localStorage.setItem('tj_reactions', JSON.stringify(this.userReactions));
        } catch {}
    }

    // ── Générer le HTML d'un bouton de réaction ──────────────────────────────

    /**
     * Crée un bouton de réaction complet pour une publication
     * @param {number|string} pubId  - ID de la publication
     * @param {object} initialCounts - { like: 5, love: 2, haha: 0, ... }
     * @returns {string} HTML complet
     */
    createReactionButton(pubId, initialCounts = {}) {
        this.reactionCounts[pubId] = {
            like: 0, love: 0, haha: 0, wow: 0, sad: 0, angry: 0,
            ...initialCounts
        };

        const userReaction = this.userReactions[pubId] || null;
        const totalCount   = this._getTotal(pubId);
        const btnLabel     = userReaction ? REACTIONS[userReaction].label : 'J\'aime';
        const btnEmoji     = userReaction ? REACTIONS[userReaction].emoji : '👍';
        const btnStyle     = userReaction
            ? `background:${REACTIONS[userReaction].gradient};border-color:transparent;color:white;`
            : '';
        const reacted      = userReaction ? 'reacted' : '';

        return `
<div class="fb-reaction-wrap" id="react-wrap-${pubId}" data-pub-id="${pubId}">
    <button
        class="btn-fb-react ${reacted}"
        id="react-btn-${pubId}"
        style="${btnStyle}"
        data-pub-id="${pubId}"
        onclick="window.ReactionSystem.handleQuickClick(${pubId})"
        onmouseenter="window.ReactionSystem.startHoverTimer(${pubId})"
        onmouseleave="window.ReactionSystem.startCloseTimer(${pubId})"
        ontouchstart="window.ReactionSystem.startLongPress(${pubId}, event)"
        ontouchend="window.ReactionSystem.cancelLongPress()"
        aria-label="Réactions"
    >
        <span class="react-emoji" id="react-emoji-${pubId}">${btnEmoji}</span>
        <span class="react-label" id="react-label-${pubId}">${btnLabel}</span>
    </button>
    <div
        class="reactions-popup"
        id="react-popup-${pubId}"
        onmouseenter="window.ReactionSystem.keepPopupOpen(${pubId})"
        onmouseleave="window.ReactionSystem.startCloseTimer(${pubId})"
    >
        ${Object.entries(REACTIONS).map(([type, info]) => `
        <div class="reaction-option" onclick="window.ReactionSystem.react(${pubId},'${type}')" title="${info.label}">
            <span class="r-emoji">${info.emoji}</span>
            <span class="r-label">${info.label}</span>
        </div>`).join('')}
    </div>
</div>`;
    }

    /**
     * Crée la barre de compteurs (à placer sous le bouton)
     * @param {number|string} pubId
     * @returns {string} HTML
     */
    createCounterBar(pubId) {
        return `
<div class="reactions-summary-bar" id="react-bar-${pubId}"
     onmouseenter="window.ReactionSystem.showDetailTooltip(${pubId}, this)"
     onmouseleave="window.ReactionSystem.hideDetailTooltip()"
     onclick="window.ReactionSystem.showDetailTooltip(${pubId}, this, true)">
    <span id="react-pills-${pubId}"></span>
    <span class="react-total" id="react-total-${pubId}">0</span>
    <span style="font-size:.72rem;">réactions</span>
</div>`;
    }

    // ── Interactions ─────────────────────────────────────────────────────────

    /**
     * Clic rapide sur le bouton principal
     * - Si déjà réagi → annuler la réaction
     * - Sinon → like par défaut
     */
    handleQuickClick(pubId) {
        const current = this.userReactions[pubId];
        if (current) {
            this.react(pubId, current); // toggle off
        } else {
            this.react(pubId, 'like');
        }
    }

    /**
     * Réagir avec un type spécifique
     * @param {number|string} pubId
     * @param {string} type - 'like'|'love'|'haha'|'wow'|'sad'|'angry'
     */
    react(pubId, type) {
        const current = this.userReactions[pubId];
        const counts  = this.reactionCounts[pubId] || {};

        // Toggle : si même réaction → annuler
        if (current === type) {
            counts[type] = Math.max(0, (counts[type] || 0) - 1);
            delete this.userReactions[pubId];
            this._updateButton(pubId, null);
        } else {
            // Annuler l'ancienne réaction si elle existe
            if (current && counts[current] !== undefined) {
                counts[current] = Math.max(0, counts[current] - 1);
            }
            // Ajouter la nouvelle
            counts[type] = (counts[type] || 0) + 1;
            this.userReactions[pubId] = type;
            this._updateButton(pubId, type);
            this._showFloatEmoji(pubId, REACTIONS[type].emoji);
        }

        this.reactionCounts[pubId] = counts;
        this._updateCounter(pubId);
        this._saveToStorage();
        this.closePopup(pubId);

        // Envoi API (optionnel — ne bloque pas si l'endpoint n'existe pas)
        this._sendToAPI(pubId, type, current === type ? null : type);
    }

    /**
     * Mettre à jour l'apparence du bouton
     */
    _updateButton(pubId, type) {
        const btn   = document.getElementById(`react-btn-${pubId}`);
        const emoji = document.getElementById(`react-emoji-${pubId}`);
        const label = document.getElementById(`react-label-${pubId}`);
        if (!btn) return;

        if (type && REACTIONS[type]) {
            const info = REACTIONS[type];
            btn.style.background    = info.gradient;
            btn.style.borderColor   = 'transparent';
            btn.style.color         = 'white';
            btn.classList.add('reacted');
            if (emoji) emoji.textContent = info.emoji;
            if (label) label.textContent = info.label;
        } else {
            btn.style.background  = '';
            btn.style.borderColor = '';
            btn.style.color       = '';
            btn.classList.remove('reacted');
            if (emoji) emoji.textContent = '👍';
            if (label) label.textContent = 'J\'aime';
        }
    }

    /**
     * Mettre à jour le compteur
     */
    _updateCounter(pubId) {
        const counts   = this.reactionCounts[pubId] || {};
        const total    = this._getTotal(pubId);
        const totalEl  = document.getElementById(`react-total-${pubId}`);
        const pillsEl  = document.getElementById(`react-pills-${pubId}`);

        if (totalEl) totalEl.textContent = total;

        // Générer les pills (top 3 réactions non nulles)
        if (pillsEl) {
            const top = Object.entries(counts)
                .filter(([, count]) => count > 0)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 3);

            pillsEl.innerHTML = top.map(([type]) =>
                `<span class="react-pill"><span class="pill-emoji">${REACTIONS[type].emoji}</span></span>`
            ).join('');
        }

        // Synchroniser avec les anciens éléments du forum
        const legacyEl = document.getElementById(`totalReactions-${pubId}`);
        if (legacyEl) legacyEl.textContent = total;
    }

    _getTotal(pubId) {
        const counts = this.reactionCounts[pubId] || {};
        return Object.values(counts).reduce((sum, c) => sum + (c || 0), 0);
    }

    // ── Popup hover logic ────────────────────────────────────────────────────

    startHoverTimer(pubId) {
        clearTimeout(this._hoverTimer);
        clearTimeout(this._closeTimer);
        this._hoverTimer = setTimeout(() => this.openPopup(pubId), 600);
    }

    startCloseTimer(pubId) {
        clearTimeout(this._hoverTimer);
        clearTimeout(this._closeTimer);
        this._closeTimer = setTimeout(() => this.closePopup(pubId), 400);
    }

    keepPopupOpen(pubId) {
        clearTimeout(this._closeTimer);
    }

    openPopup(pubId) {
        // Fermer l'autre popup ouverte
        if (this._openPopup && this._openPopup !== pubId) {
            this.closePopup(this._openPopup);
        }
        const popup = document.getElementById(`react-popup-${pubId}`);
        if (popup) {
            popup.classList.add('visible');
            this._openPopup = pubId;
        }
    }

    closePopup(pubId) {
        const popup = document.getElementById(`react-popup-${pubId}`);
        if (popup) popup.classList.remove('visible');
        if (this._openPopup === pubId) this._openPopup = null;
    }

    // ── Gestion tactile (long press → popup) ─────────────────────────────────

    startLongPress(pubId, event) {
        clearTimeout(this._longPressTimer);
        this._longPressTimer = setTimeout(() => {
            event.preventDefault();
            this.openPopup(pubId);
        }, 500);
    }

    cancelLongPress() {
        clearTimeout(this._longPressTimer);
    }

    // ── Tooltip détaillé ──────────────────────────────────────────────────────

    showDetailTooltip(pubId, anchorEl, forceShow = false) {
        const counts = this.reactionCounts[pubId] || {};
        const total  = this._getTotal(pubId);
        if (total === 0) return;

        // Supprimer l'ancien tooltip
        this.hideDetailTooltip();

        const tooltip = document.createElement('div');
        tooltip.className = 'reactions-detail-tooltip';
        tooltip.id = 'react-tooltip-global';

        const rows = Object.entries(counts)
            .filter(([, count]) => count > 0)
            .sort((a, b) => b[1] - a[1])
            .map(([type, count]) =>
                `<div class="tooltip-row">
                    <span>${REACTIONS[type].emoji} ${REACTIONS[type].label}</span>
                    <span>${count}</span>
                </div>`
            ).join('');

        tooltip.innerHTML = `<div style="font-weight:700;margin-bottom:6px;font-size:.8rem;border-bottom:1px solid rgba(255,255,255,.2);padding-bottom:5px;">Réactions (${total})</div>${rows}`;

        document.body.appendChild(tooltip);

        // Positionner
        const rect = anchorEl.getBoundingClientRect();
        tooltip.style.position = 'fixed';
        tooltip.style.left = `${rect.left}px`;
        tooltip.style.top  = `${rect.top - tooltip.offsetHeight - 10}px`;

        requestAnimationFrame(() => tooltip.classList.add('show'));

        // Auto-fermeture si forceShow (clic mobile)
        if (forceShow) {
            setTimeout(() => this.hideDetailTooltip(), 3000);
        }
    }

    hideDetailTooltip() {
        const existing = document.getElementById('react-tooltip-global');
        if (existing) existing.remove();
    }

    // ── Animation emoji flottant ──────────────────────────────────────────────

    _showFloatEmoji(pubId, emoji) {
        const btn = document.getElementById(`react-btn-${pubId}`);
        if (!btn) return;

        const rect  = btn.getBoundingClientRect();
        const float = document.createElement('div');
        float.className   = 'reaction-float-notif';
        float.textContent = emoji;
        float.style.left  = `${rect.left + rect.width / 2 - 20}px`;
        float.style.top   = `${rect.top + window.scrollY - 10}px`;

        document.body.appendChild(float);
        setTimeout(() => float.remove(), 1300);
    }

    // ── Envoi API (optionnel) ─────────────────────────────────────────────────

    async _sendToAPI(pubId, type, newType) {
        try {
            await fetch(`/publication/${pubId}/react`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ type: newType || null })
            });
        } catch {
            // Silencieux — l'état localStorage est déjà sauvé
        }
    }

    // ── Initialisation d'une page ─────────────────────────────────────────────

    /**
     * Initialise tous les boutons de réaction existants sur la page
     * À appeler en fin de DOMContentLoaded
     * @param {NodeList|Array} cards - éléments avec data-id
     */
    initPage(cards) {
        cards.forEach(card => {
            const pubId = card.getAttribute('data-id');
            if (!pubId) return;

            // Restaurer la réaction sauvegardée
            const savedReaction = this.userReactions[pubId];
            if (savedReaction) {
                this._updateButton(pubId, savedReaction);
            }

            // Récupérer les compteurs depuis le DOM si disponible
            const legacyTotal = parseInt(
                document.getElementById(`totalReactions-${pubId}`)?.textContent || '0'
            );
            if (!this.reactionCounts[pubId]) {
                this.reactionCounts[pubId] = { like: legacyTotal, love: 0, haha: 0, wow: 0, sad: 0, angry: 0 };
            }

            this._updateCounter(pubId);
        });
    }

    /**
     * Remplace le bouton J'aime simple existant par le nouveau système
     * @param {string} pubId
     * @param {HTMLElement} container - élément contenant le bouton existant
     * @param {object} initialCounts
     */
    replaceLikeButton(pubId, container, initialCounts = {}) {
        if (!container) return;
        container.innerHTML = this.createReactionButton(pubId, initialCounts) +
                              this.createCounterBar(pubId);
        this._updateCounter(pubId);

        // Restaurer réaction sauvegardée
        const saved = this.userReactions[pubId];
        if (saved) this._updateButton(pubId, saved);
    }
}

// ─── Instance globale ─────────────────────────────────────────────────────────

window.ReactionSystem = new ReactionSystem();

// ─── Fermer popup au clic en dehors ──────────────────────────────────────────

document.addEventListener('click', function(e) {
    if (!e.target.closest('.fb-reaction-wrap')) {
        if (window.ReactionSystem._openPopup) {
            window.ReactionSystem.closePopup(window.ReactionSystem._openPopup);
        }
        window.ReactionSystem.hideDetailTooltip();
    }
}, true);

// ─── Compatibilité avec quickReact() existant dans forum_public.html.twig ────

window.quickReact = function(pubId) {
    window.ReactionSystem.handleQuickClick(pubId);
};

console.log('✅ Reactions Service chargé — Système Facebook (6 réactions, popup, compteurs)');