<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    // NOTIFICATIONS : récupérer les non-lues
    // =========================================================
    // =========================================================
// NOTIFICATIONS : récupérer UNIQUEMENT les réductions de prix
// =========================================================
#[Route('/notifications', name: 'app_notifications_list', methods: ['GET'])]
public function notifications(Connection $connection): JsonResponse
{
    // 🔥 FILTRE : seulement les notifications de type 'reduction' (pas 'reservation')
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
    // =========================================================
// NOTIFICATIONS : marquer une notification comme lue
// =========================================================
#[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['POST'])]
public function markRead(Connection $connection, int $id): JsonResponse
{
    // 🔥 Vérifier que c'est bien une réduction (sécurité)
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
    #[Route('/admin/update-prix/{idV}', name: 'app_voyage_update_prix', methods: ['POST'])]
    public function updatePrix(Request $request, Connection $connection, int $idV): JsonResponse
    {
        $nouveauPrix = (float) $request->request->get('prix');

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
    #[Route('/admin/save/{idV}', name: 'app_voyage_admin_save', methods: ['POST'])]
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
        $humeur      = trim($request->request->get('humeur', ''));
        $preferences = trim($request->request->get('preferences', ''));
        $budget      = trim($request->request->get('budget', ''));
        $saison      = trim($request->request->get('saison', ''));

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
                $clean = trim(preg_replace('/```json|```/i', '', $result));
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
        $prompt   = rawurlencode($promptText);
        $url      = "https://text.pollinations.ai/$prompt";
        $ch       = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_USERAGENT => 'TunisiaJourney/1.0']);
        $response = curl_exec($ch);
        $error    = curl_errno($ch);
        curl_close($ch);

        if (!$error && $response) {
            $clean = trim(preg_replace('/```json|```/i', '', $response));
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
    // ROUTE IA CHATBOT — ULTRA INTELLIGENT (Gemini + contexte)
    // =========================================================
    #[Route('/ai/ask', name: 'app_ai_ask', methods: ['POST'])]
    public function askAI(Request $request): JsonResponse
    {
        $question = trim($request->request->get('question', ''));
        $history  = json_decode($request->request->get('history', '[]'), true) ?: [];

        if ($question === '') {
            return $this->json(['answer' => "Bonjour ! Je suis votre guide TunisiaJourney. Posez-moi n'importe quelle question sur la Tunisie, nos voyages, la météo, la gastronomie..."]);
        }

        // Récupérer la météo en temps réel si la question le demande
        $weatherContext = '';
        if (preg_match('/\b(météo|temps|température|pluie|soleil|weather|chaud|froid|aujourd\'hui|ce soir|cette semaine)\b/ui', $question)) {
            $weatherContext = $this->fetchWeatherContext($question);
        }

        $systemPrompt = $this->buildSystemPrompt($weatherContext);

        $apiKey = $this->getGeminiKey();

        // ---- GEMINI (prioritaire) ----
        if ($apiKey) {
            $answer = $this->callGeminiChat($apiKey, $systemPrompt, $history, $question);
            if ($answer) {
                return $this->json(['answer' => $answer]);
            }
        }

        // ---- GROQ (fallback gratuit) ----
        $groqKey = $_ENV['GROQ_API_KEY'] ?? null;
        if ($groqKey) {
            $answer = $this->callGroq($groqKey, $systemPrompt, $history, $question);
            if ($answer) {
                return $this->json(['answer' => $answer]);
            }
        }

        // ---- POLLINATIONS (fallback sans clé) ----
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
        $nom = trim($request->request->get('nom', ''));
        if ($nom === '') return $this->json(['error' => 'Nom manquant'], 400);

        $promptText = "Décris la destination touristique \"$nom\" en Tunisie en exactement 2 phrases courtes, poétiques et évocatrices. Réponds uniquement avec ces 2 phrases, rien d'autre.";

        $apiKey = $this->getGeminiKey();
        if ($apiKey) {
            $result = $this->callGeminiRaw($apiKey, $promptText, 150);
            if ($result) return $this->json(['description' => trim($result)]);
        }

        $prompt   = rawurlencode($promptText);
        $ch       = curl_init("https://text.pollinations.ai/$prompt");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'TunisiaJourney/1.0']);
        $response = curl_exec($ch);
        $error    = curl_errno($ch);
        curl_close($ch);

        if ($error || !$response) return $this->json(['error' => 'Service IA indisponible'], 503);
        return $this->json(['description' => trim($response)]);
    }

    // =========================================================
    // PROMPT SYSTÈME — Le cerveau du chatbot
    // =========================================================
    private function buildSystemPrompt(string $weatherContext = ''): string
    {
        $now    = date('d/m/Y H:i');
        $saison = $this->getCurrentSeason();

        $weather = $weatherContext
            ? "\n\n=== MÉTÉO EN TEMPS RÉEL ===\n$weatherContext"
            : "";

        return <<<PROMPT
Tu es **TunisiaJourney AI**, le guide conversationnel officiel de l'agence de voyage TunisiaJourney, spécialisée dans le tourisme en Tunisie.

Date et heure actuelles : $now
Saison actuelle : $saison
$weather

## TON RÔLE
Tu es un expert polyvalent capable de répondre à TOUTES les questions liées à :
- L'agence TunisiaJourney et ses offres de voyages
- La Tunisie sous tous ses aspects (histoire, culture, géographie, actualité, pratique)
- La météo et le climat en Tunisie (utilise les données temps réel si disponibles)
- La gastronomie tunisienne et les restaurants
- Les hôtels, riads, hébergements
- Les événements, festivals, actualités touristiques
- Les activités, sports, loisirs
- Les transports, visas, conseils pratiques
- La boutique souvenirs et artisanat tunisien
- Le forum et les avis voyageurs
- Les programmes et itinéraires de voyages
- Les réservations et informations tarifaires
- Toute question générale (tu peux aussi répondre aux questions hors-sujet avec intelligence et rediriger vers la Tunisie quand c'est pertinent)

## L'AGENCE TUNISIAJOURNEY
- Agence de voyage en ligne spécialisée Tunisie
- Propose : voyages thématiques (plage, désert, culture, gastronomie, sport), programmes journaliers, hébergements, activités
- Prix en Dinars Tunisiens (DT/TND)
- Services : réservation en ligne, guide vocal IA, recommandation par humeur, livre numérique Tunisie
- Contact : via le site tunisiajourney.tn
- Sections du site : Voyages, Hôtels, Événements, Forum, Boutique, Gastronomie

## TUNISIE — CONNAISSANCES CLÉS
**Géographie** : 163 610 km², au nord de l'Afrique, entre Algérie et Libye. Côte méditerranéenne au nord et à l'est, désert saharien au sud.

**Villes principales** : Tunis (capitale), Sfax, Sousse, Kairouan, Bizerte, Monastir, Nabeul, Hammamet, Djerba, Tozeur, Douz, Mahdia, Tabarka

**Sites UNESCO** : Médina de Tunis, Carthage, Amphithéâtre d'El Jem, Médina de Sousse, Médina de Kairouan, Dougga, Kerkouane

**Gastronomie** : Couscous (plat national), Brik à l'œuf, Lablabi, Chakchouka, Mechouia, Merguez, Kafteji, Ojja, Mloukhia, Osban, Tajine tunisien. Pâtisseries : Makroudh, Bambalouni, Zlabia, Samsa, Baklawa. Boissons : Thé à la menthe, Boukha (figue), Thibarine, vins tunisiens (Magon, Vieux Magon, Coteaux de Carthage).

**Hôtels recommandés** : Four Seasons Tunis, The Residence Tunis, Hasdrubal Thalassa (Yasmine Hammamet), Movenpick Resort Djerba, Radisson Blu Hammamet, Seabel Rym Beach Djerba, Dar Dhiafa (Djerba), Le Zephyr (Tabarka), Dar Said (Sidi Bou Saïd)

**Festivals** : Festival de Carthage (juillet-août), Festival de Djerba (été), Festival du Désert à Douz (décembre), Festival de Jazz à Tabarka (juillet), Festival de la Médina (Tunis), Journées de Carthage (théâtre et cinéma), Fête de l'Octopus à Mahdia

**Activités** : Plongée (Tabarka, Mahdia), Kitesurf (Djerba), Golf (Monastir, Hammamet, Tabarka), Quad et dromadaire (Sahara), Randonnée (Ain Draham), Thalasso, Visites archéologiques, Shopping médinas

**Transports** : Tunisair (compagnie nationale), Aéroports de Tunis-Carthage, Monastir, Djerba-Zarzis, Enfidha-Hammamet. SNCFT (trains), Louages (taxis collectifs), STT (bus nationaux)

**Pratique** : Visa non requis pour ressortissants UE, Algérie, Maroc. Monnaie : Dinar Tunisien (1 EUR ≈ 3,35 DT). Langue : Arabe officiel, français très répandu. Décalage horaire : UTC+1 (été UTC+2). Religion : Islam (pays laïc ouvert). Meilleure période : avril-juin et septembre-octobre.

**Climat par région** :
- Nord et côte : Méditerranéen (étés chauds secs, hivers doux pluvieux)
- Centre : Semi-aride
- Sud (Sahara) : Désertique (très chaud en été, frais la nuit en hiver)
Températures : Été côte 28-35°C, Sahara 40-48°C. Hiver côte 12-18°C, montagnes 5-10°C.

## STYLE DE RÉPONSE
- Réponds TOUJOURS en français naturel et chaleureux
- Sois concis mais complet : 2-4 phrases max pour les questions simples, plus détaillé pour les questions complexes
- Utilise quelques emojis pertinents (pas d'excès)
- Si la question concerne la météo, utilise les données temps réel fournies
- Pour les prix, cite des fourchettes réalistes en DT
- Propose toujours une action concrète ou une recommandation quand c'est pertinent
- Si tu ne sais pas quelque chose de très spécifique (ex : prix exact d'un hôtel en temps réel), dis-le honnêtement et oriente vers les bonnes ressources
- Tu peux répondre aux questions générales non-Tunisie mais ramène naturellement la conversation vers TunisiaJourney quand c'est pertinent
- Ne te répète JAMAIS entre les réponses successives
PROMPT;
    }

    // =========================================================
    // MÉTÉO EN TEMPS RÉEL — OpenWeatherMap (gratuit)
    // =========================================================
    private function fetchWeatherContext(string $question): string
    {
        $weatherKey = $this->getWeatherKey();
        if (!$weatherKey) {
            // Sans clé : données statiques basées sur la saison
            return $this->getStaticWeatherContext();
        }

        // Détecter la ville mentionnée dans la question
        $city = $this->detectCity($question);

        $url = "https://api.openweathermap.org/data/2.5/weather?q={$city},TN&appid={$weatherKey}&units=metric&lang=fr";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        if ($errno || !$response) return $this->getStaticWeatherContext();

        $data = json_decode($response, true);
        if (!$data || isset($data['cod']) && $data['cod'] != 200) {
            return $this->getStaticWeatherContext();
        }

        $temp        = round($data['main']['temp']);
        $feelsLike   = round($data['main']['feels_like']);
        $humidity    = $data['main']['humidity'];
        $desc        = $data['weather'][0]['description'] ?? 'ciel dégagé';
        $wind        = round($data['wind']['speed'] * 3.6); // m/s → km/h
        $cityName    = $data['name'] ?? $city;

        return "Météo actuelle à {$cityName} (Tunisie) : {$temp}°C (ressenti {$feelsLike}°C), {$desc}, humidité {$humidity}%, vent {$wind} km/h. Données OpenWeatherMap en temps réel.";
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
            if (strpos($q, $key) !== false) return $name;
        }
        return 'Tunis'; // défaut
    }

    private function getStaticWeatherContext(): string
    {
        $saison = $this->getCurrentSeason();
        $contexts = [
            'printemps' => 'Printemps en Tunisie : températures douces 18-26°C, ciel généralement ensoleillé, légère brise, idéal pour visiter. Quelques pluies possibles au nord.',
            'été'       => 'Été en Tunisie : fortes chaleurs 30-45°C selon les régions, mer chaude 26-28°C, ensoleillement maximal. Sahara très chaud (40-48°C). Hydratation essentielle.',
            'automne'   => 'Automne en Tunisie : températures agréables 20-28°C, mer encore chaude, moins de touristes. Parfait pour les visites culturelles et le désert.',
            'hiver'     => 'Hiver en Tunisie : côte nord 10-16°C parfois pluvieux, sud 15-22°C ensoleillé. Montagnes parfois enneigées (Ain Draham). Désert magnifique avec nuits fraîches.',
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
    // APPEL GEMINI — Chat multi-tours
    // =========================================================
    private function callGeminiChat(string $apiKey, string $systemPrompt, array $history, string $question): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        // Construire les messages avec l'historique
        $contents = [];

        // Injecter le system prompt comme premier message user/model
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => "Voici tes instructions système :\n\n" . $systemPrompt . "\n\nCompris ? Réponds juste 'Oui, je suis prêt !' brièvement."]]
        ];
        $contents[] = [
            'role'  => 'model',
            'parts' => [['text' => 'Oui, je suis prêt !']]
        ];

        // Historique de conversation (max 10 derniers échanges)
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $geminiRole = $msg['role'] === 'user' ? 'user' : 'model';
                $contents[] = [
                    'role'  => $geminiRole,
                    'parts' => [['text' => $msg['content']]]
                ];
            }
        }

        // Question actuelle
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $question]]
        ];

        $payload = [
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => 0.85,
                'topK'            => 40,
                'topP'            => 0.95,
                'maxOutputTokens' => 600,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',        'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',  'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',  'threshold' => 'BLOCK_ONLY_HIGH'],
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 20,
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) { curl_close($ch); return null; }
        curl_close($ch);

        $data   = json_decode($response, true);
        $text   = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        return $text ? trim($text) : null;
    }

    // =========================================================
    // APPEL GEMINI — Sans historique (pour description/recommend)
    // =========================================================
    private function callGeminiRaw(string $apiKey, string $prompt, int $maxTokens = 500): ?string
    {
        $url     = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
        $payload = [
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => $maxTokens],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 20,
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) { curl_close($ch); return null; }
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    // =========================================================
    // APPEL GROQ — Fallback ultra-rapide (llama3-70b gratuit)
    // =========================================================
    private function callGroq(string $apiKey, string $systemPrompt, array $history, string $question): ?string
    {
        $url      = 'https://api.groq.com/openai/v1/chat/completions';
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach (array_slice($history, -8) as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        $payload = [
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => $messages,
            'max_tokens'  => 600,
            'temperature' => 0.85,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => 20,
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) { curl_close($ch); return null; }
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    // =========================================================
    // APPEL POLLINATIONS — Fallback sans clé API
    // =========================================================
    private function callPollinationsChat(string $systemPrompt, array $history, string $question): ?string
    {
        // Construire un prompt condensé
        $context = "Instructions : " . substr($systemPrompt, 0, 800) . "\n\n";

        foreach (array_slice($history, -4) as $msg) {
            $role     = $msg['role'] === 'user' ? 'Utilisateur' : 'Assistant';
            $context .= "{$role}: {$msg['content']}\n";
        }
        $context .= "Utilisateur: {$question}\nAssistant:";

        $encoded  = rawurlencode($context);
        $url      = "https://text.pollinations.ai/{$encoded}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'TunisiaJourney/1.0',
        ]);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        return (!$errno && $response) ? trim($response) : null;
    }

    // =========================================================
    // SHOW / PROGRAMMES
    // =========================================================
    #[Route('/{idV}', name: 'app_voyage_show')]
    public function show(Connection $connection, int $idV): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$idV]);
        if (!$voyage) throw $this->createNotFoundException('Voyage non trouvé');
        $programmes = $connection->fetchAllAssociative("SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC", [$idV]);
        return $this->render('voyage/show.html.twig', ['voyage' => $voyage, 'programmes' => $programmes]);
    }

    #[Route('/{idV}/programmes', name: 'app_voyage_programmes')]
    public function programmes(Connection $connection, int $idV): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$idV]);
        if (!$voyage) throw $this->createNotFoundException('Voyage non trouvé');
        $programmes = $connection->fetchAllAssociative("SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC", [$idV]);
        return $this->render('voyage/programmes.html.twig', ['voyage' => $voyage, 'programmes' => $programmes]);
    }
}