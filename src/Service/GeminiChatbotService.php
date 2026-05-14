<?php
// src/Service/GeminiChatbotService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class GeminiChatbotService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    private const FREE_MODELS = [
        'openrouter/free',
        'meta-llama/llama-3.3-70b-instruct:free',
        'deepseek/deepseek-r1-distill-llama-70b:free',
        'qwen/qwen3-8b:free',
        'google/gemma-2-9b-it:free',
        'mistralai/mistral-7b-instruct:free',
    ];

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $apiKey           = $params->get('gemini_api_key_forum');
        $this->apiKey     = is_string($apiKey) ? $apiKey : '';
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    /**
     * @param array<int, array{role: string, content: string}> $conversationHistory
     * @return array{success: bool, response: string, error: string|null}
     */
    public function sendMessage(string $message, array $conversationHistory = []): array
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'your_')) {
            return [
                'success'  => false,
                'response' => '⚠️ Le chatbot n\'est pas configuré. Ajoutez votre clé OpenRouter dans .env (GEMINI_API_KEY).',
                'error'    => 'Clé API manquante.',
            ];
        }

        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()],
        ];

        foreach ($conversationHistory as $item) {
            $messages[] = [
                'role'    => $item['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $item['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        foreach (self::FREE_MODELS as $model) {
            $result = $this->callOpenRouter($model, $messages);

            if ($result['success']) {
                return $result;
            }

            if (isset($result['retry']) && $result['retry']) {
                $this->logger->warning('[OpenRouter] Modèle ' . $model . ' indisponible, essai suivant...');
                continue;
            }

            return $result;
        }

        return [
            'success'  => false,
            'response' => '⚠️ Tous les modèles gratuits sont temporairement indisponibles. Réessayez dans quelques minutes.',
            'error'    => 'Tous les modèles ont échoué',
        ];
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{success: bool, response: string, error: string|null, retry?: bool}
     */
    private function callOpenRouter(string $model, array $messages): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer'  => 'https://tunisiajourney.com',
                    'X-Title'       => 'TunisiaJourney Chatbot',
                ],
                'json' => [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => 800,
                    'temperature' => 0.7,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $data       = $response->toArray(false);

            if ($statusCode !== 200) {
                $apiError = $data['error']['message'] ?? ('HTTP ' . $statusCode);
                $this->logger->error('[OpenRouter] ' . $model . ' — Erreur : ' . $apiError);

                if (in_array($statusCode, [401, 403])) {
                    return [
                        'success'  => false,
                        'response' => '🔑 Clé OpenRouter invalide. Vérifiez GEMINI_API_KEY dans votre .env.',
                        'error'    => $apiError,
                        'retry'    => false,
                    ];
                }

                return [
                    'success'  => false,
                    'response' => '',
                    'error'    => $apiError,
                    'retry'    => true,
                ];
            }

            $text = $data['choices'][0]['message']['content'] ?? null;

            if (empty($text)) {
                return [
                    'success'  => false,
                    'response' => '',
                    'error'    => 'Réponse vide',
                    'retry'    => true,
                ];
            }

            return [
                'success'  => true,
                'response' => $this->cleanResponse((string) $text),
                'error'    => null,
            ];

        } catch (\Exception $e) {
            $this->logger->error('[OpenRouter] Exception : ' . $e->getMessage());
            return [
                'success'  => false,
                'response' => '',
                'error'    => $e->getMessage(),
                'retry'    => true,
            ];
        }
    }

    private function cleanResponse(string $text): string
    {
        // Fix ligne 174 : preg_replace peut retourner null → cast (string)
        $text = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        // Fix ligne 177 : explode attend string, $text est garanti string après le cast
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);

        return trim(implode("\n", $lines));
    }

    private function getSystemPrompt(): string
    {
        return "Tu es un assistant intelligent et chaleureux pour TunisiaJourney, un forum de voyage en Tunisie.

Ton role est d'aider les utilisateurs avec :
1. Le forum : publier, commenter, reagir, modifier son profil, signaler un contenu
2. Les destinations tunisiennes : Djerba, Hammamet, Sousse, Tunis, Monastir, Tozeur, Tabarka, Mahdia, Sidi Bou Said, Carthage, Matmata
3. La gastronomie : couscous, brik, lablabi, chorba, makroudh, fricasse, harissa, merguez
4. Les conseils pratiques : meilleures periodes, transports, securite, budget, hebergements, culture et traditions
5. L'histoire et la culture tunisienne : Carthage, les Berberes, l'ere ottomane, le jasmin, la medina de Tunis

Regles de reponse :
- Reponds TOUJOURS en francais sauf si l'utilisateur ecrit dans une autre langue
- Sois amical, enthousiaste et utilise des emojis pertinents
- Reponses concises : 2 a 4 paragraphes maximum
- Si tu ne sais pas quelque chose, dis-le honnetement
- Encourage toujours l'utilisateur a partager ses experiences sur le forum

Infos pratiques :
- Pour publier : bouton 'Partager votre aventure' en haut du forum
- Pour commenter : cliquer sur l'image ou le bouton 'Commenter'
- Profil : avatar en haut a droite vers Mon profil
- Reactions : survoler ou maintenir le bouton J'aime pour voir toutes les reactions";
    }
}
