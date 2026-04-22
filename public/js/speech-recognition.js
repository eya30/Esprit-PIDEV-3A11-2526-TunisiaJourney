// public/js/speech-recognition.js

class SpeechRecognitionService {
    constructor() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        
        if (!SpeechRecognition) {
            console.warn('Speech recognition not supported');
            this.isSupported = false;
            return;
        }
        
        this.isSupported = true;
        this.recognition = new SpeechRecognition();
        this.isListening = false;
        this.currentTextarea = null;
        this.onResultCallback = null;
        this.interimTimeout = null;
        
        this.recognition.continuous = false;
        this.recognition.interimResults = true;
        this.recognition.lang = 'fr-FR';
        this.recognition.maxAlternatives = 1;
        
        this.recognition.onresult = (event) => this.handleResult(event);
        this.recognition.onerror = (event) => this.handleError(event);
        this.recognition.onend = () => this.handleEnd();
        this.recognition.onstart = () => this.handleStart();
    }
    
    setLanguage(lang) {
        if (!this.isSupported) return;
        const langMap = {
            'fr': 'fr-FR', 'en': 'en-US', 'ar': 'ar-EG',
            'es': 'es-ES', 'de': 'de-DE', 'it': 'it-IT',
            'pt': 'pt-PT', 'ru': 'ru-RU'
        };
        this.recognition.lang = langMap[lang] || 'fr-FR';
    }
    
    startListening(textareaId, onResult) {
        if (!this.isSupported) {
            this.showToast('⚠️ Reconnaissance vocale non supportée', 'warn');
            return false;
        }
        
        if (this.isListening) {
            this.stopListening();
            return false;
        }
        
        this.currentTextarea = document.getElementById(textareaId);
        if (!this.currentTextarea) return false;
        
        this.onResultCallback = onResult;
        
        try {
            this.recognition.start();
            return true;
        } catch (error) {
            this.showToast('❌ Impossible de démarrer la reconnaissance vocale', 'error');
            return false;
        }
    }
    
    stopListening() {
        if (!this.isSupported || !this.isListening) return;
        try { this.recognition.stop(); } catch(e) {}
    }
    
    handleResult(event) {
        let interimTranscript = '';
        let finalTranscript = '';
        
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0].transcript;
            if (event.results[i].isFinal) {
                finalTranscript += transcript;
            } else {
                interimTranscript += transcript;
            }
        }
        
        if (interimTranscript) {
            this.showInterimResult(interimTranscript);
        }
        
        if (finalTranscript && this.currentTextarea) {
            this.addToTextarea(finalTranscript);
            if (this.onResultCallback) this.onResultCallback(finalTranscript);
        }
    }
    
    addToTextarea(text) {
        if (!this.currentTextarea) return;
        
        const currentValue = this.currentTextarea.value;
        const cursorPos = this.currentTextarea.selectionStart;
        const prefix = currentValue && !currentValue.endsWith(' ') && !currentValue.endsWith('\n') && currentValue.length > 0 ? ' ' : '';
        const suffix = text.endsWith(' ') || text.endsWith('.') || text.endsWith('!') || text.endsWith('?') ? '' : ' ';
        
        const newValue = currentValue.slice(0, cursorPos) + prefix + text.charAt(0).toUpperCase() + text.slice(1) + suffix + currentValue.slice(cursorPos);
        this.currentTextarea.value = newValue;
        
        const inputEvent = new Event('input', { bubbles: true });
        this.currentTextarea.dispatchEvent(inputEvent);
        
        const newCursorPos = cursorPos + prefix.length + text.length + suffix.length;
        this.currentTextarea.setSelectionRange(newCursorPos, newCursorPos);
        this.currentTextarea.focus();
    }
    
    showInterimResult(text) {
        let interimDiv = document.getElementById('speechInterimResult');
        if (!interimDiv && this.currentTextarea) {
            interimDiv = document.createElement('div');
            interimDiv.id = 'speechInterimResult';
            interimDiv.className = 'speech-interim-result';
            this.currentTextarea.parentNode.insertBefore(interimDiv, this.currentTextarea.nextSibling);
        }
        
        if (interimDiv) {
            interimDiv.innerHTML = `<i class="fas fa-microphone-alt" style="color:#3B82F6; margin-right:5px;"></i> 🎙️ ${this.escapeHtml(text)}`;
            interimDiv.style.display = 'block';
            
            clearTimeout(this.interimTimeout);
            this.interimTimeout = setTimeout(() => {
                if (interimDiv) interimDiv.style.display = 'none';
            }, 2000);
        }
    }
    
    handleError(event) {
        let errorMessage = '';
        switch(event.error) {
            case 'no-speech': errorMessage = '⚠️ Aucune parole détectée'; break;
            case 'audio-capture': errorMessage = '❌ Microphone non trouvé'; break;
            case 'not-allowed': errorMessage = '🔒 Permission microphone refusée'; break;
            default: errorMessage = '❌ Erreur: ' + event.error;
        }
        this.showToast(errorMessage, 'error');
        this.stopListening();
    }
    
    handleStart() {
        this.isListening = true;
        this.showMicrophoneState(true);
        this.showToast('🎙️ Parlez maintenant...', 'info', 2000);
    }
    
    handleEnd() {
        this.isListening = false;
        this.showMicrophoneState(false);
        
        setTimeout(() => {
            const interimDiv = document.getElementById('speechInterimResult');
            if (interimDiv) interimDiv.remove();
        }, 2000);
    }
    
    showMicrophoneState(isListening) {
        const micButtons = document.querySelectorAll('.mic-btn');
        micButtons.forEach(btn => {
            if (isListening) {
                btn.classList.add('listening');
                btn.innerHTML = '<i class="fas fa-microphone-slash"></i> <span>Arrêter</span>';
                btn.style.background = '#EF4444';
            } else {
                btn.classList.remove('listening');
                btn.innerHTML = '<i class="fas fa-microphone"></i> <span>Dicter</span>';
                btn.style.background = 'linear-gradient(135deg, #3B82F6, #2563EB)';
            }
        });
    }
    
    showToast(message, type, duration = 3000) {
        const colors = { success: '#27ae60', error: '#c0392b', warn: '#e07b39', info: '#2980b9' };
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.cssText = `position:fixed; bottom:80px; right:20px; background:${colors[type]}; color:white; padding:10px 20px; border-radius:40px; font-size:.8rem; z-index:10001; animation:fadeIn 0.3s ease;`;
        toast.innerHTML = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
}

window.SpeechRecognitionService = new SpeechRecognitionService();

const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 70% { box-shadow: 0 0 0 10px rgba(239,68,68,0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); } }
    .mic-btn { transition: all 0.3s ease; cursor: pointer; }
    .mic-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
    .mic-btn.listening { animation: pulse 1.5s infinite; }
    .mic-btn.listening:hover { background: #DC2626; }
    .speech-interim-result { animation: fadeIn 0.3s ease; font-size: 0.8rem; color: #666; font-style: italic; margin-top: 5px; padding: 8px 12px; background: #FEF9F0; border-radius: 10px; border-left: 3px solid #3B82F6; }
`;
document.head.appendChild(style);