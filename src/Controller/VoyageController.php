<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\CurrencyProgService;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/voyage')]
class VoyageController extends AbstractController
{
    // =========================================================
    // CONFIG — lues depuis .env
    // =========================================================
    private function getGeminiKey(): ?string
    {
        return $_ENV['GEMINI_API_KEY'] ?? null;
    }

    private function getWeatherKey(): ?string
    {
        return $_ENV['OPENWEATHER_API_KEY'] ?? null;
    }

    // =========================================================
    // INDEX
    // =========================================================
    #[Route('/', name: 'app_voyage_index')]
    public function index(Connection $connection): Response
    {
        $voyages = $connection->fetchAllAssociative(
            "SELECT * FROM voyages ORDER BY idV DESC"
        );

        return $this->render('voyage/index.html.twig', [
            'voyages' => $voyages
        ]);
    }

    // =========================================================
    // NOTIFICATIONS : récupérer UNIQUEMENT les réductions de prix
    // =========================================================
    #[Route('/notifications', name: 'app_notifications_list', methods: ['GET'])]
    public function notifications(Connection $connection): JsonResponse
    {
        $notifications = $connection->fetchAllAssociative(
            "SELECT * FROM notifications
             WHERE type = 'reduction'
             ORDER BY date_creation DESC
             LIMIT 20"
        );

        return $this->json([
            'notifications' => $notifications,
            'count'         => count($notifications),
        ]);
    }

    // =========================================================
    // NOTIFICATIONS : marquer une notification comme lue
    // =========================================================
    #[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markRead(Connection $connection, int $id): JsonResponse
    {
        $connection->executeStatement(
            "UPDATE notifications SET lu = 1 WHERE id = ? AND type = 'reduction'",
            [$id]
        );
        return $this->json(['success' => true]);
    }

    // =========================================================
    // NOTIFICATIONS : marquer toutes comme lues
    // =========================================================
    #[Route('/notifications/read-all', name: 'app_notifications_read_all', methods: ['POST'])]
    public function markAllRead(Connection $connection): JsonResponse
    {
        $connection->executeStatement("UPDATE notifications SET lu = 1 WHERE lu = 0");
        return $this->json(['success' => true]);
    }

    // =========================================================
    // ADMIN : Mettre à jour le prix d'un voyage
    // =========================================================
    #[Route('/admin/update-prix/{idV}', name: 'app_voyage_update_prix', methods: ['POST'], requirements: ['idV' => '\d+'])]
    public function updatePrix(Request $request, Connection $connection, int $idV): JsonResponse
    {
        $nouveauPrix = (float) $request->request->get('prix', 0);

        $voyage = $connection->fetchAssociative(
            "SELECT nom, prix FROM voyages WHERE idV = ?",
            [$idV]
        );

        if (!$voyage) {
            return $this->json(['error' => 'Voyage non trouvé'], 404);
        }

        $ancienPrix = (float) $voyage['prix'];

        $connection->executeStatement(
            "UPDATE voyages SET prix = ? WHERE idV = ?",
            [$nouveauPrix, $idV]
        );

        if (abs($ancienPrix - $nouveauPrix) > 0.01) {
            $diff     = $nouveauPrix - $ancienPrix;
            $pct      = $ancienPrix > 0 ? round(abs($diff / $ancienPrix) * 100) : 0;
            $sens     = $diff < 0 ? '↓ Baisse' : '↑ Hausse';
            $message  = sprintf(
                '%s de prix ! %s : %.0f DT → %.0f DT (%d%%)',
                $sens,
                $voyage['nom'],
                $ancienPrix,
                $nouveauPrix,
                $pct
            );

            $connection->executeStatement(
                "INSERT INTO notifications (type, message, voyage_id, voyage_nom, lu, date_creation)
                 VALUES (?, ?, ?, ?, 0, NOW())",
                ['reduction', $message, $idV, $voyage['nom']]
            );
        }

        return $this->json(['success' => true, 'prix' => $nouveauPrix]);
    }

    // =========================================================
    // ADMIN : Sauvegarder un voyage
    // =========================================================
    #[Route('/admin/save/{idV}', name: 'app_voyage_admin_save', methods: ['POST'], requirements: ['idV' => '\d+'])]
    public function adminSave(Request $request, Connection $connection, int $idV = 0): JsonResponse
    {
        $data = $request->request->all();
        $nouveauPrix = isset($data['prix']) ? (float) $data['prix'] : null;

        if ($idV > 0 && $nouveauPrix !== null) {
            $voyage = $connection->fetchAssociative(
                "SELECT nom, prix FROM voyages WHERE idV = ?",
                [$idV]
            );

            if ($voyage) {
                $ancienPrix = (float) $voyage['prix'];

                $connection->executeStatement(
                    "UPDATE voyages SET prix = ?, nom = ?, description = ? WHERE idV = ?",
                    [$nouveauPrix, $data['nom'] ?? $voyage['nom'], $data['description'] ?? '', $idV]
                );

                if (abs($ancienPrix - $nouveauPrix) > 0.01) {
                    $this->createPriceNotification($connection, $idV, $voyage['nom'], $ancienPrix, $nouveauPrix);
                }

                return $this->json(['success' => true]);
            }
        }

        return $this->json(['success' => false, 'error' => 'Données manquantes'], 400);
    }

    private function createPriceNotification(
        Connection $connection,
        int        $voyageId,
        string     $voyageNom,
        float      $ancienPrix,
        float      $nouveauPrix
    ): void {
        $diff    = $nouveauPrix - $ancienPrix;
        $pct     = $ancienPrix > 0 ? round(abs($diff / $ancienPrix) * 100) : 0;
        $sens    = $diff < 0 ? '↓ Baisse' : '↑ Hausse';
        $message = sprintf(
            '%s de prix ! %s : %.0f DT → %.0f DT (%d%%)',
            $sens,
            $voyageNom,
            $ancienPrix,
            $nouveauPrix,
            $pct
        );

        $connection->executeStatement(
            "INSERT INTO notifications (type, message, voyage_id, voyage_nom, lu, date_creation)
             VALUES ('reduction', ?, ?, ?, 0, NOW())",
            [$message, $voyageId, $voyageNom]
        );
    }

    // =========================================================
    // RECOMMANDATION PAR HUMEUR/PRÉFÉRENCES
    // =========================================================
    #[Route('/ai/recommend', name: 'app_ai_recommend', methods: ['POST'])]
    public function recommend(Request $request, Connection $connection): JsonResponse
    {
        $humeur      = trim((string) $request->request->get('humeur', ''));
        $preferences = trim((string) $request->request->get('preferences', ''));
        $budget      = trim((string) $request->request->get('budget', ''));
        $saison      = trim((string) $request->request->get('saison', ''));

        $voyages = $connection->fetchAllAssociative(
            "SELECT idV, nom, description, prix, capacite FROM voyages ORDER BY idV DESC"
        );

        if (empty($voyages)) {
            return $this->json(['error' => 'Aucun voyage disponible'], 404);
        }

        $voyagesList = implode("\n", array_map(function($v) {
            return "- ID {$v['idV']} : {$v['nom']} (Prix: {$v['prix']} DT) — {$v['description']}";
        }, $voyages));

        $promptText =
            "Tu es un conseiller de voyages expert en Tunisie. " .
            "Un client cherche un voyage avec ces préférences :\n" .
            "- Humeur : $humeur\n" .
            "- Préférences : $preferences\n" .
            "- Budget : $budget DT\n" .
            "- Saison préférée : $saison\n\n" .
            "Voici les voyages disponibles :\n$voyagesList\n\n" .
            "Réponds UNIQUEMENT en JSON valide avec exactement ce format (rien d'autre) :\n" .
            "{\"idV\": <id>, \"nom\": \"<nom>\", \"raison\": \"<explication courte>\"}";

        // Essayer Gemini d'abord
        $apiKey = $this->getGeminiKey();
        if ($apiKey) {
            $result = $this->callGeminiRaw($apiKey, $promptText, 300);
            if ($result) {
                $clean = trim((string) preg_replace('/```json|```/i', '', $result));
                $data  = json_decode($clean, true);
                if ($data && isset($data['idV'])) {
                    $recommended = array_values(array_filter($voyages, fn($v) => $v['idV'] == $data['idV']))[0] ?? $voyages[0];
                    return $this->json([
                        'idV'    => $recommended['idV'],
                        'nom'    => $recommended['nom'],
                        'raison' => $data['raison'] ?? 'Ce voyage correspond parfaitement à vos préférences.',
                        'prix'   => $recommended['prix']
                    ]);
                }
            }
        }

        // Fallback Pollinations
        $response = $this->httpGetRequest("https://text.pollinations.ai/" . rawurlencode($promptText), 25);
        if ($response !== null) {
            $clean = trim((string) preg_replace('/```json|```/i', '', $response));
            $data  = json_decode($clean, true);
            if ($data && isset($data['idV'])) {
                $recommended = array_values(array_filter($voyages, fn($v) => $v['idV'] == $data['idV']))[0] ?? $voyages[0];
                return $this->json([
                    'idV'    => $recommended['idV'],
                    'nom'    => $recommended['nom'],
                    'raison' => $data['raison'] ?? 'Ce voyage correspond parfaitement à vos préférences.',
                    'prix'   => $recommended['prix']
                ]);
            }
        }

        // Dernier fallback aléatoire
        $random = $voyages[array_rand($voyages)];
        return $this->json([
            'idV'    => $random['idV'],
            'nom'    => $random['nom'],
            'raison' => 'Ce voyage correspond à vos envies d\'aventure en Tunisie.',
            'prix'   => $random['prix']
        ]);
    }

    // =========================================================
    // ROUTE IA CHATBOT
    // =========================================================
    #[Route('/ai/ask', name: 'app_ai_ask', methods: ['POST'])]
    public function askAI(Request $request): JsonResponse
    {
        $question = trim((string) $request->request->get('question', ''));
        $historyRaw = $request->request->get('history', '[]');
        $history = json_decode(is_string($historyRaw) ? $historyRaw : '[]', true) ?: [];

        if ($question === '') {
            return $this->json(['answer' => "Bonjour ! Je suis votre guide TunisiaJourney. Posez-moi n'importe quelle question sur la Tunisie, nos voyages, la météo, la gastronomie..."]);
        }

        $weatherContext = '';
        if (preg_match('/\b(météo|temps|température|pluie|soleil|weather|chaud|froid|aujourd\'hui|ce soir|cette semaine)\b/ui', $question)) {
            $weatherContext = $this->fetchWeatherContext($question);
        }

        $systemPrompt = $this->buildSystemPrompt($weatherContext);

        $apiKey = $this->getGeminiKey();

        if ($apiKey) {
            $answer = $this->callGeminiChat($apiKey, $systemPrompt, $history, $question);
            if ($answer) {
                return $this->json(['answer' => $answer]);
            }
        }

        $groqKey = $_ENV['GROQ_API_KEY'] ?? null;
        if ($groqKey) {
            $answer = $this->callGroq($groqKey, $systemPrompt, $history, $question);
            if ($answer) {
                return $this->json(['answer' => $answer]);
            }
        }

        $answer = $this->callPollinationsChat($systemPrompt, $history, $question);
        if ($answer) {
            return $this->json(['answer' => $answer]);
        }

        return $this->json(['answer' => "Je suis désolé, le service IA est momentanément indisponible. Réessayez dans quelques instants !"]);
    }

    // =========================================================
    // ROUTE IA : description courte
    // =========================================================
    #[Route('/ai/description', name: 'app_ai_description', methods: ['POST'])]
    public function aiDescription(Request $request): JsonResponse
    {
        $nom = trim((string) $request->request->get('nom', ''));
        if ($nom === '') return $this->json(['error' => 'Nom manquant'], 400);

        $promptText = "Décris la destination touristique \"$nom\" en Tunisie en exactement 2 phrases courtes, poétiques et évocatrices. Réponds uniquement avec ces 2 phrases, rien d'autre.";

        $apiKey = $this->getGeminiKey();
        if ($apiKey) {
            $result = $this->callGeminiRaw($apiKey, $promptText, 150);
            if ($result) return $this->json(['description' => trim($result)]);
        }

        $response = $this->httpGetRequest("https://text.pollinations.ai/" . rawurlencode($promptText), 20);
        if ($response !== null) {
            return $this->json(['description' => trim($response)]);
        }

        return $this->json(['error' => 'Service IA indisponible'], 503);
    }

    // =========================================================
    // MÉTHODES UTILITAIRES HTTP
    // =========================================================
    /**
     * Effectue une requête HTTP GET
     *
     * @return string|null La réponse ou null en cas d'erreur
     */
    private function httpGetRequest(string $url, int $timeout = 15): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_USERAGENT      => 'TunisiaJourney/1.0',
        ]);
        
        $response = curl_exec($ch);
        $error = curl_errno($ch);
        
        // Correction: curl_exec peut retourner false ou une string
        if ($error || $response === false || !is_string($response)) {
            return null;
        }
        
        return $response;
    }

    /**
     * Effectue une requête HTTP POST avec JSON
     *
     * @param array<string, mixed> $payload Le payload JSON
     * @param list<string> $headers En-têtes supplémentaires
     * @return array<string, mixed>|null La réponse décodée ou null en cas d'erreur
     */
    private function httpPostRequest(string $url, array $payload, array $headers = [], int $timeout = 20): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        
        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            return null;
        }
        
        $defaultHeaders = ['Content-Type: application/json'];
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $allHeaders,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_TIMEOUT        => $timeout,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_errno($ch);
        
        if ($error || $response === false || !is_string($response)) {
            return null;
        }
        
        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    // =========================================================
    // CONVERSION DE DEVISES
    // =========================================================
    #[Route('/convert-price', name: 'app_convert_price', methods: ['POST'])]
    public function convertPrice(Request $request, CurrencyProgService $currencyProgService): JsonResponse
    {
        $amount = (float) $request->request->get('amount', 0);
        $from   = strtoupper((string) $request->request->get('from', 'TND'));
        $to     = strtoupper((string) $request->request->get('to', 'EUR'));

        $converted = $currencyProgService->convert($amount, $from, $to);
        $formatted = $currencyProgService->formatPrice($converted, $to);
        $rate      = $currencyProgService->getExchangeRate($from, $to);

        return $this->json([
            'success'            => true,
            'original_amount'    => $amount,
            'original_currency'  => $from,
            'converted_amount'   => $converted,
            'converted_currency' => $to,
            'formatted'          => $formatted,
            'exchange_rate'      => $rate,
            'last_update'        => date('d/m/Y H:i:s')
        ]);
    }

    #[Route('/convert-price-simple', name: 'app_convert_price_simple', methods: ['POST'])]
    public function convertPriceSimple(Request $request, CurrencyProgService $currencyProgService): JsonResponse
    {
        $amount = (float) $request->request->get('amount', 0);
        $to     = strtoupper((string) $request->request->get('to', 'EUR'));

        $converted = $currencyProgService->convert($amount, 'TND', $to);
        $formatted = $currencyProgService->formatPrice($converted, $to);

        return $this->json([
            'success'            => true,
            'original_amount'    => $amount,
            'original_currency'  => 'TND',
            'converted_amount'   => $converted,
            'converted_currency' => $to,
            'formatted'          => $formatted,
            'exchange_rate'      => $currencyProgService->getExchangeRate('TND', $to)
        ]);
    }

    #[Route('/currencies-list', name: 'app_currencies_list', methods: ['GET'])]
    public function getCurrenciesList(CurrencyProgService $currencyProgService): JsonResponse
    {
        return $this->json([
            'currencies' => $currencyProgService->getAvailableCurrencies()
        ]);
    }

    // =========================================================
    // SHOW / PROGRAMMES
    // =========================================================
    #[Route('/{idV}', name: 'app_voyage_show', requirements: ['idV' => '\d+'])]
    public function show(Connection $connection, CurrencyProgService $currencyProgService, int $idV): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$idV]);
        if (!$voyage) throw $this->createNotFoundException('Voyage non trouvé');

        $programmes = $connection->fetchAllAssociative(
            "SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC",
            [$idV]
        );

        $allPrices     = [];
        $currencies    = $currencyProgService->getAvailableCurrencies();
        $originalPrice = (float) $voyage['prix'];

        foreach (array_keys($currencies) as $currency) {
            $allPrices[$currency] = [
                'formatted' => $currencyProgService->formatPrice(
                    $currencyProgService->convert($originalPrice, 'TND', $currency),
                    $currency
                ),
                'rate' => $currencyProgService->getExchangeRate('TND', $currency)
            ];
        }

        return $this->render('voyage/show.html.twig', [
            'voyage'      => $voyage,
            'programmes'  => $programmes,
            'all_prices'  => $allPrices,
            'currencies'  => $currencies
        ]);
    }

    #[Route('/{idV}/programmes', name: 'app_voyage_programmes', requirements: ['idV' => '\d+'])]
    public function programmes(Connection $connection, int $idV): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$idV]);
        if (!$voyage) throw $this->createNotFoundException('Voyage non trouvé');

        $programmes = $connection->fetchAllAssociative(
            "SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC",
            [$idV]
        );

        return $this->render('voyage/programmes.html.twig', [
            'voyage'     => $voyage,
            'programmes' => $programmes
        ]);
    }

    // =========================================================
    // PROMPT SYSTÈME
    // =========================================================
    private function buildSystemPrompt(string $weatherContext = ''): string
    {
        $now    = date('d/m/Y H:i');
        $saison = $this->getCurrentSeason();

        $weather = $weatherContext
            ? "\n\n=== MÉTÉO EN TEMPS RÉEL ===\n$weatherContext"
            : "";

        return <<<PROMPT
Tu es **TunisiaJourney AI**, le guide conversationnel officiel de l'agence de voyage TunisiaJourney.

Date et heure actuelles : $now
Saison actuelle : $saison
$weather

## TON RÔLE
Tu es un expert polyvalent capable de répondre à TOUTES les questions liées à la Tunisie et l'agence.

## STYLE DE RÉPONSE
- Réponds TOUJOURS en français naturel et chaleureux
- Sois concis mais complet : 2-4 phrases max pour les questions simples
- Utilise quelques emojis pertinents (pas d'excès)
- Ne te répète JAMAIS entre les réponses successives
PROMPT;
    }

    // =========================================================
    // MÉTÉO
    // =========================================================
    private function fetchWeatherContext(string $question): string
    {
        $weatherKey = $this->getWeatherKey();
        if (!$weatherKey) {
            return $this->getStaticWeatherContext();
        }

        $city = $this->detectCity($question);

        $url = "https://api.openweathermap.org/data/2.5/weather?q={$city},TN&appid={$weatherKey}&units=metric&lang=fr";
        $data = $this->httpGetRequest($url, 5);
        
        if ($data === null) {
            return $this->getStaticWeatherContext();
        }
        
        $weatherData = json_decode($data, true);
        if (!$weatherData || isset($weatherData['cod']) && $weatherData['cod'] != 200) {
            return $this->getStaticWeatherContext();
        }

        $temp      = round($weatherData['main']['temp']);
        $feelsLike = round($weatherData['main']['feels_like']);
        $humidity  = $weatherData['main']['humidity'];
        $desc      = $weatherData['weather'][0]['description'] ?? 'ciel dégagé';
        $wind      = round($weatherData['wind']['speed'] * 3.6);
        $cityName  = $weatherData['name'] ?? $city;

        return "Météo actuelle à {$cityName} (Tunisie) : {$temp}°C (ressenti {$feelsLike}°C), {$desc}, humidité {$humidity}%, vent {$wind} km/h.";
    }

    private function detectCity(string $question): string
    {
        $q = mb_strtolower($question);
        $cities = [
            'djerba'   => 'Djerba',
            'sousse'   => 'Sousse',
            'hammamet' => 'Hammamet',
            'sfax'     => 'Sfax',
            'monastir' => 'Monastir',
            'bizerte'  => 'Bizerte',
            'nabeul'   => 'Nabeul',
            'tozeur'   => 'Tozeur',
            'tabarka'  => 'Tabarka',
            'mahdia'   => 'Mahdia',
            'kairouan' => 'Kairouan',
            'tunis'    => 'Tunis',
        ];
        foreach ($cities as $key => $name) {
            if (str_contains($q, $key)) return $name;
        }
        return 'Tunis';
    }

    private function getStaticWeatherContext(): string
    {
        $saison   = $this->getCurrentSeason();
        $contexts = [
            'printemps' => 'Printemps en Tunisie : températures douces 18-26°C, ciel ensoleillé.',
            'été'       => 'Été en Tunisie : fortes chaleurs 30-45°C, mer chaude 26-28°C.',
            'automne'   => 'Automne en Tunisie : températures agréables 20-28°C.',
            'hiver'     => 'Hiver en Tunisie : côte nord 10-16°C, sud 15-22°C ensoleillé.',
        ];
        return $contexts[$saison] ?? $contexts['printemps'];
    }

    private function getCurrentSeason(): string
    {
        $month = (int) date('n');
        if ($month >= 3 && $month <= 5)  return 'printemps';
        if ($month >= 6 && $month <= 8)  return 'été';
        if ($month >= 9 && $month <= 11) return 'automne';
        return 'hiver';
    }

    // =========================================================
    // APPELS API
    // =========================================================
    /**
     * @param string $apiKey
     * @param string $systemPrompt
     * @param array<array{role: string, content: string}> $history
     * @param string $question
     * @return string|null
     */
    private function callGeminiChat(string $apiKey, string $systemPrompt, array $history, string $question): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $contents = [
            ['role' => 'user', 'parts' => [['text' => "Instructions : " . $systemPrompt . "\n\nCompris ? Réponds 'OK'."]]],
            ['role' => 'model', 'parts' => [['text' => 'OK']]]
        ];

        foreach (array_slice($history, -10) as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $payload = [
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.85, 'maxOutputTokens' => 600],
        ];

        $result = $this->httpPostRequest($url, $payload);
        
        if ($result && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($result['candidates'][0]['content']['parts'][0]['text']);
        }
        
        return null;
    }

    private function callGeminiRaw(string $apiKey, string $prompt, int $maxTokens = 500): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => $maxTokens],
        ];

        $result = $this->httpPostRequest($url, $payload);
        
        if ($result && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($result['candidates'][0]['content']['parts'][0]['text']);
        }
        
        return null;
    }

    /**
     * @param string $apiKey
     * @param string $systemPrompt
     * @param array<array{role: string, content: string}> $history
     * @param string $question
     * @return string|null
     */
    private function callGroq(string $apiKey, string $systemPrompt, array $history, string $question): ?string
    {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach (array_slice($history, -8) as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        $payload = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => $messages,
            'max_tokens' => 600,
            'temperature' => 0.85,
        ];

        $result = $this->httpPostRequest($url, $payload, ['Authorization: Bearer ' . $apiKey]);
        
        if ($result && isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
        
        return null;
    }

    /**
     * @param string $systemPrompt
     * @param array<array{role: string, content: string}> $history
     * @param string $question
     * @return string|null
     */
    private function callPollinationsChat(string $systemPrompt, array $history, string $question): ?string
    {
        $context = "Instructions : " . substr($systemPrompt, 0, 500) . "\n\n";

        foreach (array_slice($history, -4) as $msg) {
            $role = $msg['role'] === 'user' ? 'Utilisateur' : 'Assistant';
            $context .= "{$role}: {$msg['content']}\n";
        }
        $context .= "Utilisateur: {$question}\nAssistant:";

        $response = $this->httpGetRequest("https://text.pollinations.ai/" . rawurlencode($context), 15);
        
        return $response !== null ? trim($response) : null;
    }
}