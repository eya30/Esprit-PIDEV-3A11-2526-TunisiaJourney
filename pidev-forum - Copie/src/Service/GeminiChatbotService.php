<?php
// src/Service/GeminiChatbotService.php
// ──────────────────────────────────────────────────────────────────────────────
// Utilise OpenRouter.ai — 100% GRATUIT, pas de carte bancaire requise
// Modèles gratuits disponibles : mistral, llama, gemma, etc.
// ──────────────────────────────────────────────────────────────────────────────

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class GeminiChatbotService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    // ── Modèles 100% gratuits sur OpenRouter ────────────────────────────────
    // Si le premier ne répond pas, le suivant est essayé automatiquement
    private const FREE_MODELS = [
        'openrouter/free',                              // ← routeur automatique (MEILLEUR)
    'meta-llama/llama-3.3-70b-instruct:free',       // LLaMA 3.3 70B
    'deepseek/deepseek-r1-distill-llama-70b:free',  // DeepSeek R1
    'qwen/qwen3-8b:free',                           // Qwen3 8B
    'google/gemma-2-9b-it:free',                    // Gemma 2 9B
    'mistralai/mistral-7b-instruct:free',           // Mistral 7B
];
    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->apiKey     = $params->get('gemini_api_key'); // même paramètre .env
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    public function sendMessage(string $message, array $conversationHistory = []): array
    {
        // ── Vérifier la clé API ──────────────────────────────────────────
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'your_')) {
            return [
                'success'  => false,
                'response' => '⚠️ Le chatbot n\'est pas configuré. Ajoutez votre clé OpenRouter dans .env (GEMINI_API_KEY).',
                'error'    => 'Clé API manquante.',
            ];
        }

        // ── Construire les messages au format OpenAI (compatible OpenRouter) ─
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

        // ── Essayer chaque modèle gratuit jusqu'à succès ─────────────────
        foreach (self::FREE_MODELS as $model) {
            $result = $this->callOpenRouter($model, $messages);

            if ($result['success']) {
                return $result;
            }

            // Si quota dépassé sur ce modèle → essayer le suivant
            if (isset($result['retry']) && $result['retry']) {
                $this->logger->warning('[OpenRouter] Modèle ' . $model . ' indisponible, essai suivant...');
                continue;
            }

            // Erreur bloquante (clé invalide, etc.) → arrêter
            return $result;
        }

        // Tous les modèles ont échoué
        return [
            'success'  => false,
            'response' => '⚠️ Tous les modèles gratuits sont temporairement indisponibles. Réessayez dans quelques minutes.',
            'error'    => 'Tous les modèles ont échoué',
        ];
    }

    // ── Appel à l'API OpenRouter ─────────────────────────────────────────────
    private function callOpenRouter(string $model, array $messages): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer'  => 'https://tunisiajourney.com', // votre site
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

            // ── Erreurs HTTP ─────────────────────────────────────────────
            if ($statusCode !== 200) {
                $apiError = $data['error']['message'] ?? ('HTTP ' . $statusCode);
                $this->logger->error('[OpenRouter] ' . $model . ' — Erreur : ' . $apiError);

                // Clé invalide → erreur bloquante
                if (in_array($statusCode, [401, 403])) {
                    return [
                        'success'  => false,
                        'response' => '🔑 Clé OpenRouter invalide. Vérifiez GEMINI_API_KEY dans votre .env.',
                        'error'    => $apiError,
                        'retry'    => false,
                    ];
                }

                // Quota / surcharge → essayer modèle suivant
                return [
                    'success'  => false,
                    'response' => '',
                    'error'    => $apiError,
                    'retry'    => true,
                ];
            }

            // ── Extraire la réponse ──────────────────────────────────────
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
                'response' => $this->cleanResponse($text),
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

    // ── Nettoyage de la réponse ──────────────────────────────────────────────
    private function cleanResponse(string $text): string
    {
        // Supprimer les caractères de contrôle invisibles (sans toucher \n \r)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Maximum 2 sauts de ligne consécutifs
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Nettoyer les espaces inutiles par ligne
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);

        return trim(implode("\n", $lines));
    }

    // ── Prompt système ───────────────────────────────────────────────────────
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