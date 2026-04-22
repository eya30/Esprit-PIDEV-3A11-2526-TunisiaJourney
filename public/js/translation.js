// public/js/translation.js

const TranslationManager = {
    languages: [],
    currentLang: 'fr',
    originalContent: new Map(),
    commentTranslations: new Map(),
    pendingTranslations: {},
    
    async init() {
        await this.loadLanguages();
        this.saveOriginalContent();
        this.injectCommentTranslationButtons();
    },
    
    async loadLanguages() {
        try {
            const response = await fetch('/api/translation/languages');
            const data = await response.json();
            if (data.success) {
                this.languages = data.languages;
            }
        } catch (error) {
            console.error('Erreur chargement langues:', error);
            // Langues par défaut
            this.languages = [
                {code:'fr',name:'Français',flag:'🇫🇷'},
                {code:'en',name:'English',flag:'🇬🇧'},
                {code:'ar',name:'العربية',flag:'🇸🇦'},
                {code:'es',name:'Español',flag:'🇪🇸'},
                {code:'de',name:'Deutsch',flag:'🇩🇪'},
                {code:'it',name:'Italiano',flag:'🇮🇹'},
                {code:'pt',name:'Português',flag:'🇵🇹'},
                {code:'ru',name:'Русский',flag:'🇷🇺'}
            ];
        }
    },
    
    saveOriginalContent() {
        const selectors = '[data-translate], .hero h1, .hero p, .section-title, .section-subtitle, .stat-label, .nav-tab, .card-image-title, .card-image-desc, .reel-title, .reel-desc, .video-title, .video-description';
        document.querySelectorAll(selectors).forEach(el => {
            const key = el.id || 'el-' + Math.random().toString(36).substr(2, 9);
            if (!el.id) el.id = key;
            if (!this.originalContent.has(key) && el.textContent && el.textContent.trim()) {
                this.originalContent.set(key, el.textContent);
            }
        });
    },
    
    async translatePage(targetLang) {
        if (targetLang === 'fr') {
            this.resetPageTranslation();
            return;
        }
        
        this.showToast('🌐 Traduction de la page en cours...', 'info');
        
        const elementsToTranslate = [];
        this.originalContent.forEach((originalText, elementId) => {
            const element = document.getElementById(elementId);
            if (element && originalText.trim()) {
                elementsToTranslate.push({ id: elementId, text: originalText });
            }
        });
        
        if (elementsToTranslate.length === 0) {
            this.showToast('⚠️ Aucun texte à traduire', 'warn');
            return;
        }
        
        const batchSize = 10;
        for (let i = 0; i < elementsToTranslate.length; i += batchSize) {
            const batch = elementsToTranslate.slice(i, i + batchSize);
            const texts = batch.map(item => item.text);
            
            try {
                const response = await fetch('/api/translation/translate-batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ texts: texts, targetLang: targetLang })
                });
                const data = await response.json();
                
                if (data.success) {
                    batch.forEach((item, index) => {
                        const element = document.getElementById(item.id);
                        if (element && data.translations[index]) {
                            element.textContent = data.translations[index];
                        }
                    });
                }
            } catch (error) {
                console.error('Erreur traduction batch:', error);
                this.showToast('❌ Erreur lors de la traduction', 'error');
            }
        }
        
        this.currentLang = targetLang;
        this.showToast(`✅ Page traduite en ${targetLang}`, 'success');
    },
    
    resetPageTranslation() {
        this.originalContent.forEach((originalText, elementId) => {
            const element = document.getElementById(elementId);
            if (element && element.textContent !== originalText) {
                element.textContent = originalText;
            }
        });
        this.currentLang = 'fr';
        this.showToast('↺ Texte original restauré', 'info');
    },
    
    injectCommentTranslationButtons() {
        setTimeout(() => {
            document.querySelectorAll('.comment-item').forEach(comment => {
                const commentId = comment.id?.replace('comment-', '');
                if (!commentId) return;
                
                const actionsDiv = comment.querySelector('.comment-actions');
                if (!actionsDiv || actionsDiv.querySelector('.translate-comment-btn')) return;
                
                const translateBtn = document.createElement('button');
                translateBtn.className = 'translate-comment-btn';
                translateBtn.innerHTML = '<i class="fas fa-language"></i> Traduire';
                translateBtn.style.cssText = 'background:none; border:1px solid #3B82F6; border-radius:20px; padding:4px 12px; font-size:.7rem; cursor:pointer; color:#3B82F6; margin-left:8px;';
                translateBtn.onclick = () => this.showCommentTranslationModal(commentId);
                
                actionsDiv.appendChild(translateBtn);
            });
        }, 500);
    },
    
    showCommentTranslationModal(commentId) {
        if (document.getElementById(`translationModal-${commentId}`)) return;
        
        const modalHtml = `
            <div class="translation-modal" id="translationModal-${commentId}" style="position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:20000; display:flex; align-items:center; justify-content:center;">
                <div style="background:white; border-radius:20px; padding:1.5rem; width:350px; max-width:90%;">
                    <h4 style="margin:0 0 1rem 0; color:#93032E;"><i class="fas fa-language"></i> Traduire le commentaire</h4>
                    <select id="translateLangSelect-${commentId}" style="width:100%; padding:10px; border-radius:10px; border:1px solid #ddd; margin-bottom:1rem;">
                        <option value="">-- Choisir une langue --</option>
                        ${this.languages.filter(l => l.code !== 'fr').map(l => `<option value="${l.code}">${l.flag} ${l.name}</option>`).join('')}
                    </select>
                    <div id="translationPreview-${commentId}" style="display:none; background:#FEF9F0; padding:10px; border-radius:10px; margin-bottom:1rem; font-size:.85rem;"></div>
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button onclick="TranslationManager.closeTranslationModal(${commentId})" style="padding:8px 16px; background:#F5F0E8; border:none; border-radius:10px; cursor:pointer;">Annuler</button>
                        <button id="translateBtn-${commentId}" onclick="TranslationManager.translateComment(${commentId})" style="padding:8px 16px; background:#3B82F6; color:white; border:none; border-radius:10px; cursor:pointer;">Traduire</button>
                        <button id="replaceBtn-${commentId}" style="display:none; padding:8px 16px; background:#93032E; color:white; border:none; border-radius:10px; cursor:pointer;" onclick="TranslationManager.replaceComment(${commentId})">Remplacer</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    },
    
    closeTranslationModal(commentId) {
        const modal = document.getElementById(`translationModal-${commentId}`);
        if (modal) modal.remove();
    },
    
    async translateComment(commentId) {
        const langSelect = document.getElementById(`translateLangSelect-${commentId}`);
        const targetLang = langSelect?.value;
        
        if (!targetLang) {
            this.showToast('⚠️ Veuillez sélectionner une langue', 'warn');
            return;
        }
        
        const commentDiv = document.getElementById(`comment-${commentId}`);
        const commentTextDiv = commentDiv?.querySelector('.comment-text');
        const originalText = commentTextDiv?.getAttribute('data-original-text') || commentTextDiv?.textContent;
        
        if (!originalText) return;
        
        const previewDiv = document.getElementById(`translationPreview-${commentId}`);
        const translateBtn = document.getElementById(`translateBtn-${commentId}`);
        const replaceBtn = document.getElementById(`replaceBtn-${commentId}`);
        
        previewDiv.style.display = 'block';
        previewDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traduction en cours...';
        translateBtn.disabled = true;
        
        try {
            const response = await fetch(`/commentaire/api/translate/${commentId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ targetLang: targetLang })
            });
            const data = await response.json();
            
            if (data.success) {
                previewDiv.innerHTML = `<strong>Aperçu :</strong><br><span style="color:#555;">${this.escapeHtml(data.translated)}</span>`;
                this.commentTranslations.set(commentId, {
                    original: data.original,
                    translated: data.translated,
                    targetLang: targetLang
                });
                translateBtn.style.display = 'none';
                replaceBtn.style.display = 'inline-block';
                this.showToast('✅ Traduction prête !', 'success');
            } else {
                previewDiv.innerHTML = '<span style="color:red;">Erreur de traduction</span>';
            }
        } catch (error) {
            previewDiv.innerHTML = '<span style="color:red;">Erreur de connexion</span>';
        } finally {
            translateBtn.disabled = false;
        }
    },
    
    async replaceComment(commentId) {
        const translation = this.commentTranslations.get(commentId);
        if (!translation) return;
        
        if (!confirm('Remplacer ce commentaire par sa traduction ?')) return;
        
        try {
            const response = await fetch(`/commentaire/api/replace/${commentId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    newText: translation.translated,
                    targetLang: translation.targetLang
                })
            });
            const data = await response.json();
            
            if (data.success) {
                const commentDiv = document.getElementById(`comment-${commentId}`);
                const commentTextDiv = commentDiv?.querySelector('.comment-text');
                if (commentTextDiv) {
                    if (!commentTextDiv.hasAttribute('data-original-text')) {
                        commentTextDiv.setAttribute('data-original-text', translation.original);
                    }
                    commentTextDiv.textContent = translation.translated;
                }
                this.closeTranslationModal(commentId);
                this.showToast('✅ Commentaire remplacé par la traduction !', 'success');
            }
        } catch (error) {
            this.showToast('❌ Erreur lors du remplacement', 'error');
        }
    },
    
    showToast(message, type) {
        const colors = { success: '#27ae60', error: '#c0392b', warn: '#e07b39', info: '#2980b9' };
        const toast = document.createElement('div');
        toast.className = 'translation-toast';
        toast.style.cssText = `position:fixed; bottom:80px; right:20px; background:${colors[type]}; color:white; padding:10px 20px; border-radius:40px; font-size:.8rem; z-index:10001; animation:fadeIn 0.3s ease;`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },
    
    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
};

document.addEventListener('DOMContentLoaded', () => TranslationManager.init());