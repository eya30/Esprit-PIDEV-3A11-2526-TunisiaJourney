// deepseek-chatbot.js - Version complète

class TunisiaJourneyAI {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.isListening = false;
        this.recognition = null;
        this.sessionId = this.generateSessionId();
        this.apiUrl = 'http://localhost:8001';
        this.isProcessing = false;
        this.init();
    }
    
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    async init() {
        this.createChatbotUI();
        await this.checkHealth();
        this.initSpeechRecognition();
        this.addEventListeners();
        this.addWelcomeMessage();
    }
    
    async checkHealth() {
        try {
            const response = await fetch(`${this.apiUrl}/health`);
            const data = await response.json();
            console.log('✅ IA Service:', data);
            
            const statusEl = document.getElementById('aiStatus');
            if (statusEl) {
                if (data.deep_learning) {
                    statusEl.innerHTML = '🧠 IA Deep Learning';
                    statusEl.style.background = '#27ae60';
                } else {
                    statusEl.innerHTML = '📝 Mode règles';
                    statusEl.style.background = '#e67e22';
                }
            }
            
            return true;
        } catch (error) {
            console.error('❌ IA Service indisponible:', error);
            const statusEl = document.getElementById('aiStatus');
            if (statusEl) {
                statusEl.innerHTML = '⚠️ IA hors ligne';
                statusEl.style.background = '#c0392b';
            }
            return false;
        }
    }
    
    createChatbotUI() {
        // Bouton flottant
        const button = document.createElement('button');
        button.className = 'chatbot-button';
        button.innerHTML = '🧠';
        button.onclick = () => this.toggleChat();
        document.body.appendChild(button);
        
        // Fenêtre de chat
        const chatWindow = document.createElement('div');
        chatWindow.className = 'chatbot-window';
        chatWindow.id = 'chatbotWindow';
        chatWindow.innerHTML = `
            <div class="chatbot-header">
                <h3>
                    <span>🧠</span>
                    TunisiaJourney AI
                    <span style="font-size: 0.7rem; margin-left: 8px;" id="aiStatus">🔍 Vérification...</span>
                </h3>
                <div style="display: flex; gap: 8px;">
                    <button onclick="window.chatbot.resetConversation()" style="background: none; border: none; color: white; cursor: pointer; font-size: 16px;" title="Nouvelle conversation">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button onclick="window.chatbot.toggleChat()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">✕</button>
                </div>
            </div>
            <div class="chatbot-messages" id="chatbotMessages"></div>
            <div class="chatbot-suggestions" id="chatbotSuggestions"></div>
            <div class="chatbot-input-area">
                <textarea class="chatbot-input" id="chatbotInput" placeholder="Posez votre question à l'IA..." rows="1"></textarea>
                <button class="chatbot-mic-btn" id="chatbotMicBtn" title="Dictée vocale">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="chatbot-send-btn" id="chatbotSendBtn" title="Envoyer">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        `;
        document.body.appendChild(chatWindow);
        
        this.renderSuggestions();
    }
    
    renderSuggestions() {
        const container = document.getElementById('chatbotSuggestions');
        if (!container) return;
        
        const suggestions = [
            { icon: "📝", text: "Comment poster une publication ?" },
            { icon: "🏝️", text: "Meilleurs endroits en Tunisie ?" },
            { icon: "🍽️", text: "Spécialités culinaires ?" },
            { icon: "💙", text: "Sidi Bou Saïd que visiter ?" },
            { icon: "✈️", text: "Conseils pour voyager pas cher" },
            { icon: "🛡️", text: "La Tunisie est-elle sûre ?" },
            { icon: "🏛️", text: "Histoire de Carthage" },
            { icon: "🌊", text: "Djerba ou Hammamet ?" }
        ];
        
        container.innerHTML = suggestions.map(s => 
            `<span class="suggestion-chip" onclick="window.chatbot.sendMessageText('${this.escapeHtml(s.text)}')">
                ${s.icon} ${s.text}
            </span>`
        ).join('');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    initSpeechRecognition() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'fr-FR';
            this.recognition.continuous = false;
            this.recognition.interimResults = false;
            
            this.recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                const input = document.getElementById('chatbotInput');
                if (input) {
                    input.value = transcript;
                    this.sendMessage();
                }
                this.stopListening();
            };
            
            this.recognition.onerror = () => this.stopListening();
            this.recognition.onend = () => this.stopListening();
        }
    }
    
    startListening() {
        if (this.recognition) {
            this.isListening = true;
            this.recognition.start();
            const micBtn = document.getElementById('chatbotMicBtn');
            if (micBtn) {
                micBtn.classList.add('listening');
                micBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
            }
            this.showToast("🎤 Je vous écoute...", "info");
        }
    }
    
    stopListening() {
        if (this.recognition && this.isListening) {
            this.recognition.stop();
            this.isListening = false;
            const micBtn = document.getElementById('chatbotMicBtn');
            if (micBtn) {
                micBtn.classList.remove('listening');
                micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            }
        }
    }
    
    toggleChat() {
        this.isOpen = !this.isOpen;
        const chatWindow = document.getElementById('chatbotWindow');
        if (chatWindow) {
            chatWindow.classList.toggle('open', this.isOpen);
            if (this.isOpen) {
                document.getElementById('chatbotInput')?.focus();
                this.scrollToBottom();
            }
        }
    }
    
    sendMessageText(text) {
        const input = document.getElementById('chatbotInput');
        if (input) {
            input.value = text;
            this.sendMessage();
        }
    }
    
    async sendMessage() {
        if (this.isProcessing) {
            this.showToast("⏳ Je réfléchis encore...", "info");
            return;
        }
        
        const input = document.getElementById('chatbotInput');
        const message = input.value.trim();
        
        if (!message) return;
        
        this.addMessage(message, 'user');
        input.value = '';
        input.style.height = 'auto';
        
        this.showTypingIndicator();
        this.isProcessing = true;
        
        try {
            const response = await fetch(`${this.apiUrl}/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message,
                    session_id: this.sessionId
                })
            });
            
            const data = await response.json();
            this.hideTypingIndicator();
            
            if (data.success && data.response) {
                let confidenceHtml = '';
                if (data.confidence) {
                    const percent = Math.round(data.confidence * 100);
                    const emoji = percent > 80 ? '🎯' : (percent > 50 ? '📚' : '🤔');
                    confidenceHtml = `<span style="font-size: 0.65rem; opacity: 0.6; margin-left: 8px;">${emoji} ${percent}%</span>`;
                }
                
                let sourceHtml = '';
                if (data.source === 'knowledge_base') {
                    sourceHtml = '<span style="font-size: 0.65rem; background: #e8f5e9; padding: 2px 6px; border-radius: 10px; margin-left: 8px;">📚 Base</span>';
                } else if (data.source === 'ai_generation') {
                    sourceHtml = '<span style="font-size: 0.65rem; background: #e3f2fd; padding: 2px 6px; border-radius: 10px; margin-left: 8px;">🤖 IA</span>';
                }
                
                this.addMessage(data.response + confidenceHtml + sourceHtml, 'bot');
                this.speakResponse(data.response);
            } else {
                this.addMessage("❌ Désolé, une erreur s'est produite. Veuillez réessayer.", 'bot');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.hideTypingIndicator();
            this.addMessage(
                "⚠️ **Service IA indisponible**\n\n" +
                "Pour utiliser le chatbot intelligent, veuillez lancer le service Python :\n\n" +
                "```bash\n./start-chatbot.sh\n```\n\n" +
                "En attendant, vous pouvez parcourir le forum ! 📚",
                'bot'
            );
        } finally {
            this.isProcessing = false;
        }
        
        this.scrollToBottom();
    }
    
    async resetConversation() {
        try {
            await fetch(`${this.apiUrl}/reset?session_id=${this.sessionId}`, { method: 'POST' });
            this.messages = [];
            const container = document.getElementById('chatbotMessages');
            if (container) container.innerHTML = '';
            this.addWelcomeMessage();
            this.showToast("🔄 Nouvelle conversation", "success");
        } catch (error) {
            console.error('Erreur reset:', error);
        }
    }
    
    addWelcomeMessage() {
        setTimeout(() => {
            this.addMessage(
                "🌟 **Bienvenue sur TunisiaJourney AI !**\n\n" +
                "Je suis votre assistant intelligent. Posez-moi toutes vos questions sur :\n\n" +
                "• 📝 Le forum et ses fonctionnalités\n" +
                "• 🏝️ Les meilleurs endroits en Tunisie\n" +
                "• 🍽️ La gastronomie tunisienne\n" +
                "• ✈️ Les conseils de voyage\n" +
                "• 🏛️ L'histoire et la culture\n\n" +
                "Comment puis-je vous aider aujourd'hui ? 💙",
                'bot'
            );
        }, 500);
    }
    
    addMessage(text, type) {
        const container = document.getElementById('chatbotMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${type}`;
        
        const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div class="chatbot-message-content">
                <div class="chatbot-message-text">${this.formatMessage(text)}</div>
                <div class="chatbot-message-time">${time}</div>
            </div>
        `;
        
        container.appendChild(messageDiv);
        
        this.messages.push({ text, type, time });
        
        if (this.messages.length > 100) {
            this.messages = this.messages.slice(-100);
        }
        
        this.scrollToBottom();
    }
    
    formatMessage(text) {
        // Formater le markdown
        text = text.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Formater les listes
        text = text.replace(/^• (.*?)$/gm, '<li>$1</li>');
        text = text.replace(/(<li>.*?<\/li>)+/gs, (match) => `<ul style="margin: 8px 0; padding-left: 20px;">${match}</ul>`);
        
        // Formater les nombres
        text = text.replace(/^(\d+)\. /gm, '<strong>$1.</strong> ');
        
        // Formater les retours à la ligne
        text = text.replace(/\n/g, '<br>');
        
        // Formater les liens
        text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color: #667eea; text-decoration: underline;">$1</a>');
        
        // Formater le texte en gras pour les emojis au début
        text = text.replace(/^(\w+)/, '<strong>$1</strong>');
        
        return text;
    }
    
    speakResponse(text) {
        if ('speechSynthesis' in window) {
            if (this.currentAudio) {
                window.speechSynthesis.cancel();
            }
            
            // Nettoyer le texte pour la synthèse vocale
            let cleanText = text.replace(/<[^>]*>/g, '');
            cleanText = cleanText.replace(/[*_#]/g, '');
            
            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'fr-FR';
            utterance.rate = 0.9;
            utterance.pitch = 1;
            
            this.currentAudio = utterance;
            window.speechSynthesis.speak(utterance);
        }
    }
    
    showTypingIndicator() {
        const container = document.getElementById('chatbotMessages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message bot';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="chatbot-message-content">
                <div class="chatbot-typing">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span style="margin-left: 8px;">🤔 Réflexion en cours...</span>
                </div>
            </div>
        `;
        container.appendChild(typingDiv);
        this.scrollToBottom();
    }
    
    hideTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    }
    
    scrollToBottom() {
        const container = document.getElementById('chatbotMessages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    
    showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        const colors = { success: '#27ae60', error: '#c0392b', info: '#2980b9', warn: '#e67e22' };
        toast.style.background = colors[type] || colors.info;
        toast.innerHTML = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = '0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    addEventListeners() {
        const sendBtn = document.getElementById('chatbotSendBtn');
        const micBtn = document.getElementById('chatbotMicBtn');
        const input = document.getElementById('chatbotInput');
        
        if (sendBtn) {
            sendBtn.onclick = () => this.sendMessage();
        }
        
        if (micBtn) {
            micBtn.onclick = () => this.startListening();
        }
        
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            input.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
        }
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.chatbot = new TunisiaJourneyAI();
});