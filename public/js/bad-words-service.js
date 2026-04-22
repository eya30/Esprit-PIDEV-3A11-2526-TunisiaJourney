// public/js/bad-words-service.js
// ════════════════════════════════════════════════════════════════════════════
// ✅ BAD WORDS SERVICE — Version avancée avec détection intelligente
//    - Détection multi-langue (FR, EN, AR, TN)
//    - Détection leet speak (h4te, sh!t, etc.)
//    - Détection contournements (f.u.c.k, f-u-c-k, fuuck, etc.)
//    - Validation en temps réel avec feedback visuel
//    - Censure partielle et totale
// ════════════════════════════════════════════════════════════════════════════

class BadWordsService {
    constructor() {
        this.badWords = [
            // ── Français ────────────────────────────────────────────────
            'merde', 'putain', 'con', 'connard', 'connasse', 'salope', 'enculé',
            'enculer', 'nique', 'niquer', 'bite', 'couille', 'couilles', 'pénis',
            'vagin', 'cul', 'baiser', 'foutre', 'chier', 'chiasse', 'bordel',
            'pute', 'prostituée', 'branleur', 'branler', 'branle', 'branleuse',
            'fdp', 'fils de pute', 'ta gueule', 'ferme ta gueule', 'va te faire',
            'fumier', 'ordure', 'déchet', 'porc', 'truie', 'saligaud', 'salopard',
            'salaud', 'enfoiré', 'blaireau', 'crétin', 'abruti', 'imbécile',
            'idiot', 'débile', 'mongolien', 'attardé', 'trou du cul', 'trou de cul',
            'encule', 'va chier', 'mange merde', 'tête de con', 'gros con',
            'gueule', 'conne', 'couillons', 'couillon',

            // ── Anglais ─────────────────────────────────────────────────
            'fuck', 'fucking', 'fucker', 'fucked', 'fucks', 'shit', 'shitting',
            'shitty', 'shitter', 'bitch', 'bitches', 'bitchy', 'asshole', 'assholes',
            'dick', 'dicks', 'pussy', 'pussies', 'whore', 'whores', 'bastard',
            'bastards', 'damn', 'damned', 'cunt', 'cunts', 'cock', 'cocks',
            'nigger', 'niggers', 'nigga', 'faggot', 'fag', 'fags', 'retard',
            'retarded', 'retards', 'dumb', 'dumbass', 'dumbfuck', 'idiot',
            'moron', 'moronic', 'imbecile', 'loser', 'pathetic', 'stupid',
            'motherfucker', 'motherfucking', 'son of a bitch', 'piece of shit',
            'wtf', 'stfu', 'gtfo', 'kys', 'kill yourself', 'go die',
            'slut', 'slutty', 'hoe', 'hoes', 'whoring', 'skank', 'twat',
            'bollocks', 'wanker', 'tosser', 'bloody hell', 'prick', 'pricks',
            'douche', 'douchebag', 'jackass', 'jerk', 'scumbag', 'scum',
            'trash', 'garbage', 'filth', 'disgusting', 'vile', 'pervert',

            // ── Haine / Extrémisme ────────────────────────────────────
            'nazi', 'hitler', 'heil', 'fasciste', 'fascism', 'racism', 'raciste',
            'racist', 'antisémite', 'antisemite', 'antisemitic', 'islamophobe',
            'islamophobie', 'xénophobe', 'xenophobe', 'homophobe', 'homophobie',
            'néonazi', 'neonazi', 'kkk', 'suprémaciste', 'supremacist',
            'génocide', 'genocide', 'exterminer', 'exterminate',
            'terroriste', 'terrorist', 'terrorism', 'terrorisme', 'jihad',
            'kamikaze', 'attentat', 'bombe', 'explosif', 'arme biologique',

            // ── Violence / Crime ────────────────────────────────────────
            'viol', 'violeur', 'violer', 'rape', 'rapist', 'raper',
            'pédophile', 'pedophile', 'pedophilia', 'pédo', 'pedo',
            'meurtre', 'murder', 'murderer', 'tuer', 'assassin', 'massacrer',
            'torturer', 'torture', 'frapper', 'battre', 'violences',
            'suicide', 'se suicider', 'se tuer', 'self-harm',

            // ── LGBTQ+ insultes ─────────────────────────────────────────
            'pd', 'pédé', 'tapette', 'folle', 'gouine', 'travelo', 'trans',

            // ── Arabe / Tunisien translittéré ──────────────────────────
            'kelb', 'kalb', 'charmouta', 'sharmouta', 'charmout',
            '7ram', 'haram', 'zebi', 'zbi', 'zbel', 'tizi', 'ti9', 'tiz',
            'mcharmel', 'l3ar', 'la3r', '9ahba', 'qahba', '3ayel', '3ayla',
            'mok', 'l3an', 'la3n', '7achi', 'hachi', 'weld el kahba',
            'bint el kahba', 'ya sharmouta', 'ya kelb', 'ya 7mar', 'hmar',
            'hmara', 'khinzir', 'khanzir', 'mzya', 'msakhara', 'zenja',
            'kahba', 'bouzbal', 'bled mel3ana', 'yel3en', 'tal3ab', '9s',
            'henchour', 'bel7arma', 'hmar', 'ya barra', 'rouh mil hna',

            // ── Sexuel explicite ────────────────────────────────────────
            'pornographie', 'pornographic', 'porno', 'porn', 'xxx',
            'sexe', 'sexuel', 'sexual', 'masturbation', 'masturbate',
            'ejaculation', 'orgasme', 'orgasm', 'érection', 'erection',
            'déshabille', 'nu', 'nudité', 'nudity', 'naked', 'nude',
        ];

        // Mots à surveiller mais pas bloquer (avertissement seulement)
        this.warningWords = [
            'idiot', 'stupid', 'dumb', 'loser', 'trash', 'garbage',
            'jerk', 'idiote', 'stupide', 'nul', 'nulle', 'minable',
        ];

        // Construire les patterns
        this._buildPatterns();
    }

    // ─── Construction des patterns de détection ──────────────────────────────

    _buildPatterns() {
        // Pattern standard (mot entier)
        this.exactPattern = new RegExp(
            '(?:^|[\\s,;.!?\'"-])(' +
            this.badWords.map(w => this._escapeRegex(w)).join('|') +
            ')(?:[\\s,;.!?\'"-]|$)',
            'gi'
        );

        // Pattern leet speak (h4te, sh!t, f*ck)
        this.leetPattern = this._buildLeetPattern();

        // Pattern avec séparateurs (f.u.c.k, f-u-c-k, f_u_c_k)
        this.separatorPattern = this._buildSeparatorPattern();

        // Pattern répétition (fuuuuck, shhhhit)
        this.repetitionPattern = this._buildRepetitionPattern();
    }

    _escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    _buildLeetPattern() {
        const leetMap = {
            'a': '[a@4àáâãäå]', 'e': '[e3èéêë€]', 'i': '[i!1|lìíîï]',
            'o': '[o0òóôõö]',   's': '[s$5§]',    't': '[t7+†]',
            'g': '[g9]',        'b': '[b8ß]',      'h': '[h#]',
            'u': '[uùúûü]',     'c': '[c(©]',      'k': '[k<]',
            'n': '[nñ]',        'r': '[r®]',        'l': '[l1|]',
            'z': '[z2]',        'x': '[x×]',
        };
        const variants = this.badWords.map(word => {
            return word.split('').map(ch => leetMap[ch.toLowerCase()] || this._escapeRegex(ch)).join('');
        });
        return new RegExp('(?:^|[\\s])(' + variants.join('|') + ')(?:[\\s]|$)', 'gi');
    }

    _buildSeparatorPattern() {
        // Détecte f.u.c.k, f-u-c-k, f u c k, f*u*c*k
        const variants = this.badWords
            .filter(w => w.length >= 3)
            .map(word => {
                return word.split('').map(ch => this._escapeRegex(ch)).join('[\\s.*_\\-]{0,2}');
            });
        return new RegExp('(' + variants.join('|') + ')', 'gi');
    }

    _buildRepetitionPattern() {
        // Détecte fuuuuck (lettres répétées 3+ fois)
        const variants = this.badWords
            .filter(w => w.length >= 3)
            .map(word => {
                return word.split('').map(ch => this._escapeRegex(ch) + '+').join('');
            });
        return new RegExp('(?:^|[\\s])(' + variants.join('|') + ')(?:[\\s]|$)', 'gi');
    }

    // ─── Méthodes principales ─────────────────────────────────────────────────

    /**
     * Vérifie si le texte contient des gros mots
     * @param {string} text
     * @returns {boolean}
     */
    containsBadWords(text) {
        if (!text || typeof text !== 'string') return false;
        const normalized = this._normalizeText(text);

        // Test 1 : mots exacts
        this.exactPattern.lastIndex = 0;
        if (this.exactPattern.test(' ' + normalized + ' ')) return true;

        // Test 2 : leet speak
        this.leetPattern.lastIndex = 0;
        if (this.leetPattern.test(' ' + normalized + ' ')) return true;

        // Test 3 : séparateurs (f.u.c.k)
        this.separatorPattern.lastIndex = 0;
        if (this.separatorPattern.test(normalized)) return true;

        // Test 4 : répétition (fuuuuck)
        this.repetitionPattern.lastIndex = 0;
        if (this.repetitionPattern.test(' ' + normalized + ' ')) return true;

        return false;
    }

    /**
     * Retourne la liste des gros mots trouvés
     * @param {string} text
     * @returns {string[]}
     */
    getBadWordsFound(text) {
        if (!text || typeof text !== 'string') return [];
        const found = new Set();
        const normalized = this._normalizeText(text);
        const normalizedWithSpaces = ' ' + normalized + ' ';

        for (const badWord of this.badWords) {
            // Test exact
            const exactRegex = new RegExp(
                '(?:^|[\\s,;.!?\'"-])' + this._escapeRegex(badWord) + '(?:[\\s,;.!?\'"-]|$)', 'gi'
            );
            if (exactRegex.test(normalizedWithSpaces)) {
                found.add(badWord);
                continue;
            }
            // Test leet
            const leetWord = this._toLeetVariant(badWord);
            const leetRegex = new RegExp('(?:^|[\\s])' + leetWord + '(?:[\\s]|$)', 'gi');
            if (leetRegex.test(normalizedWithSpaces)) {
                found.add(badWord);
                continue;
            }
            // Test séparateur
            const sepWord = badWord.split('').map(ch => this._escapeRegex(ch)).join('[\\s.*_\\-]{0,2}');
            const sepRegex = new RegExp(sepWord, 'gi');
            if (badWord.length >= 4 && sepRegex.test(normalized)) {
                found.add(badWord);
                continue;
            }
            // Test répétition
            const repWord = badWord.split('').map(ch => this._escapeRegex(ch) + '+').join('');
            const repRegex = new RegExp('(?:^|[\\s])' + repWord + '(?:[\\s]|$)', 'gi');
            if (repRegex.test(normalizedWithSpaces)) {
                found.add(badWord);
            }
        }

        return Array.from(found);
    }

    /**
     * Censure le texte en remplaçant les gros mots
     * @param {string} text
     * @param {string} replacement - '***' par défaut
     * @returns {string}
     */
    censorText(text, replacement = '***') {
        if (!text || typeof text !== 'string') return text;
        let censored = text;
        for (const badWord of this.badWords) {
            const regex = new RegExp(
                '(?<=[\\s,;.!?\'"\\-]|^)' + this._escapeRegex(badWord) + '(?=[\\s,;.!?\'"\\-]|$)',
                'gi'
            );
            try {
                censored = censored.replace(regex, replacement);
            } catch (e) {
                // Fallback si lookbehind non supporté
                const fallback = new RegExp('\\b' + this._escapeRegex(badWord) + '\\b', 'gi');
                censored = censored.replace(fallback, replacement);
            }
        }
        return censored;
    }

    /**
     * Censure partielle : ne cache que le milieu du mot
     * ex: "merde" → "m***e"
     * @param {string} text
     * @returns {string}
     */
    censorPartial(text) {
        if (!text || typeof text !== 'string') return text;
        let censored = text;
        for (const badWord of this.badWords) {
            const regex = new RegExp('\\b' + this._escapeRegex(badWord) + '\\b', 'gi');
            censored = censored.replace(regex, (match) => {
                if (match.length <= 2) return '**';
                if (match.length <= 4) return match[0] + '*'.repeat(match.length - 1);
                return match[0] + '*'.repeat(match.length - 2) + match[match.length - 1];
            });
        }
        return censored;
    }

    /**
     * Validation complète d'un commentaire
     * @param {string} text
     * @returns {{ isValid: boolean, hasBadWords: boolean, hasWarnings: boolean, badWordsFound: string[], warningsFound: string[], severity: string, message: string|null }}
     */
    validateComment(text) {
        if (!text || typeof text !== 'string') {
            return {
                isValid: true, hasBadWords: false, hasWarnings: false,
                badWordsFound: [], warningsFound: [], severity: 'none', message: null
            };
        }

        const hasBadWords   = this.containsBadWords(text);
        const badWordsFound = this.getBadWordsFound(text);

        // Vérifier les mots d'avertissement
        const warningsFound = [];
        const normalizedText = this._normalizeText(text);
        for (const w of this.warningWords) {
            if (new RegExp('\\b' + this._escapeRegex(w) + '\\b', 'gi').test(normalizedText)) {
                if (!badWordsFound.includes(w)) warningsFound.push(w);
            }
        }

        // Déterminer la sévérité
        let severity = 'none';
        if (hasBadWords) {
            const severeWords = ['viol', 'rape', 'pédophile', 'pedophile', 'terrorist', 'terroriste', 'nazi', 'murder', 'meurtre', 'suicide', 'kys'];
            const isSevere = badWordsFound.some(w => severeWords.includes(w.toLowerCase()));
            severity = isSevere ? 'critical' : 'high';
        } else if (warningsFound.length > 0) {
            severity = 'low';
        }

        // Message d'erreur formaté
        let message = null;
        if (hasBadWords) {
            const displayWords = badWordsFound.slice(0, 3).map(w => `"${w}"`).join(', ');
            const more = badWordsFound.length > 3 ? ` et ${badWordsFound.length - 3} autre(s)` : '';
            message = `⚠️ Votre commentaire contient des mots inappropriés : ${displayWords}${more}. Veuillez reformuler.`;
        }

        return {
            isValid: !hasBadWords,
            hasBadWords,
            hasWarnings: warningsFound.length > 0,
            badWordsFound,
            warningsFound,
            severity,
            message
        };
    }

    /**
     * Vérifie si le texte contient uniquement des avertissements (pas bloquant)
     * @param {string} text
     * @returns {boolean}
     */
    hasOnlyWarnings(text) {
        const result = this.validateComment(text);
        return !result.hasBadWords && result.hasWarnings;
    }

    // ─── Utilitaires internes ────────────────────────────────────────────────

    _normalizeText(text) {
        return text
            .toLowerCase()
            // Normaliser les accents Unicode
            .normalize('NFD')
            // Supprimer les diacritiques (mais garder les caractères arabes)
            .replace(/[\u0300-\u036f]/g, '')
            // Remplacer les variantes leet communes
            .replace(/@/g, 'a').replace(/4/g, 'a')
            .replace(/3/g, 'e').replace(/€/g, 'e')
            .replace(/!/g, 'i').replace(/1/g, 'i')
            .replace(/0/g, 'o')
            .replace(/5/g, 's').replace(/\$/g, 's')
            .replace(/7/g, 't')
            .replace(/9/g, 'g')
            .replace(/8/g, 'b')
            .trim();
    }

    _toLeetVariant(word) {
        const leetMap = {
            'a': '[a@4àáâãäå]', 'e': '[e3èéêë€]', 'i': '[i!1|lìíîï]',
            'o': '[o0òóôõö]',   's': '[s$5§]',    't': '[t7+†]',
            'g': '[g9]',        'b': '[b8ß]',
        };
        return word.split('').map(ch => leetMap[ch] || this._escapeRegex(ch)).join('');
    }
}

// ─── Initialisation globale ───────────────────────────────────────────────────

window.BadWordsService = new BadWordsService();

// ─── Fonction helper pour validation UI en temps réel ────────────────────────
// Utilisation : checkBadWords('modalCommentText', 'badWordsErrorModal', 'badWordsErrorTextModal')

window.checkBadWords = function(textareaId, errorDivId, errorTextId) {
    const textarea = document.getElementById(textareaId);
    const errorDiv = document.getElementById(errorDivId);
    const errorText = document.getElementById(errorTextId);

    if (!textarea || !errorDiv || !errorText) return true;

    const text = textarea.value;

    if (text.trim().length < 3) {
        errorDiv.classList.remove('visible');
        textarea.style.borderColor = '';
        return true;
    }

    const result = window.BadWordsService.validateComment(text);

    if (result.hasBadWords) {
        errorText.textContent = result.message;
        errorDiv.classList.add('visible');
        textarea.style.borderColor = '#EF4444';
        textarea.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.15)';

        // Icône selon sévérité
        const icon = errorDiv.querySelector('i');
        if (icon) {
            icon.className = result.severity === 'critical'
                ? 'fas fa-ban'
                : 'fas fa-exclamation-triangle';
        }
        return false;
    }

    // Avertissements (non bloquants)
    if (result.hasWarnings) {
        errorText.textContent = `⚡ Ton de votre message : veillez à rester respectueux envers tous les voyageurs.`;
        errorDiv.classList.add('visible');
        errorDiv.style.background = '#FFFBEB';
        errorDiv.style.borderColor = '#FCD34D';
        errorDiv.style.borderLeftColor = '#F59E0B';
        errorDiv.style.color = '#92400E';
        textarea.style.borderColor = '#F59E0B';
        textarea.style.boxShadow = '0 0 0 3px rgba(245,158,11,0.15)';
        return true; // Les avertissements ne bloquent pas
    }

    // Tout va bien
    errorDiv.classList.remove('visible');
    errorDiv.style.background = '';
    errorDiv.style.borderColor = '';
    errorDiv.style.borderLeftColor = '';
    errorDiv.style.color = '';
    textarea.style.borderColor = '';
    textarea.style.boxShadow = '';
    return true;
};

console.log('✅ Bad Words Service chargé — Détection multi-langue, leet speak, séparateurs actifs');