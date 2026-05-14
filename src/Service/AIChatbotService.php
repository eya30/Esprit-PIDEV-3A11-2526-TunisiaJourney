<?php
// src/Service/AIChatbotService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AIChatbotService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey = 'sk-83566108bd77492c93581a3afed55e0d';
    
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }
    
    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $message, string $sessionId = 'default'): array
    {
        $message = trim($message);
        
        if (empty($message)) {
            return ['success' => false, 'error' => 'Message vide', 'response' => 'Veuillez écrire une question.'];
        }
        
        $systemPrompt = $this->getSystemPrompt();
        
        try {
            $response = $this->httpClient->request('POST', 'https://api.deepseek.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message]
                    ],
                    'temperature' => 0.8,
                    'max_tokens' => 800,
                ],
                'timeout' => 30,
            ]);
            
            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'response' => $data['choices'][0]['message']['content'],
                    'error' => null
                ];
            }
            
            return $this->getFallbackResponse();
            
        } catch (\Exception $e) {
            $this->logger->error('Chatbot API Error: ' . $e->getMessage());
            return $this->getFallbackResponse();
        }
    }
    
    private function getSystemPrompt(): string
    {
        return "Tu es un assistant IA super intelligent pour TunisiaJourney, un forum de voyage en Tunisie.
        
Tu dois répondre de manière amicale, détaillée et utile. Utilise des émojis.

Domaines de compétence :
- Forum : comment poster, commenter, modifier profil
- Lieux en Tunisie : Sidi Bou Saïd, Carthage, Djerba, Hammamet, Sousse, Tozeur
- Culture et gastronomie : couscous, brik, harissa
- Conseils pratiques : météo, argent, transport, sécurité

Sois chaleureux et donne des réponses détaillées en français.";
    }
    
    /**
     * @return array<string, mixed>
     */
    private function getFallbackResponse(): array
    {
        $responses = [
            "🌟 Merci pour votre message ! Je suis là pour vous aider.\n\nPour le moment, je vous invite à consulter les publications du forum ou à reformuler votre question. La communauté TunisiaJourney est très active !\n\nQue souhaitez-vous savoir exactement ? 💙",
            "🤔 Excellente question ! Je vous recommande de parcourir les forums thématiques ou d'utiliser la barre de recherche. Notre communauté est très active et répondra à vos questions ! 💬",
            "📝 Je comprends votre demande. N'hésitez pas à consulter notre FAQ ou à créer une nouvelle publication. Je suis là pour vous guider ! 🎯"
        ];
        
        return [
            'success' => true,
            'response' => $responses[array_rand($responses)],
            'error' => null
        ];
    }
}