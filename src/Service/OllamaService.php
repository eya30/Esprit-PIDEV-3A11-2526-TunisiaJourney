<?php
// src/Service/OllamaService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaService
{
    private HttpClientInterface $client;
    private MediaIntelligentService $mediaService;
    private string $ollamaUrl = 'http://localhost:11434/api/generate';

    public function __construct(HttpClientInterface $client, MediaIntelligentService $mediaService)
    {
        $this->client = $client;
        $this->mediaService = $mediaService;
    }

    /**
     * Vérifie si le serveur Ollama est disponible et répond.
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->client->request('GET', 'http://localhost:11434/', [
                'timeout' => 3,
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si un modèle d'images est disponible (ex: llava)
     */
    public function isImageModelAvailable(): bool
    {
        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model'   => 'llava',
                    'prompt'  => 'test',
                    'stream'  => false,
                ],
                'timeout' => 5,
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Génère des mots-clés pour la recherche d'images
     */
    public function generateImageKeywords(string $nom): string
    {
        $prompt = <<<PROMPT
Génère 3-4 mots-clés en anglais pour rechercher une image de voyage pour: "{$nom} en Tunisie".
Retourne UNIQUEMENT les mots-clés séparés par des espaces, sans phrase d'introduction.
Exemple: "beach mediterranean sea tourism"
PROMPT;

        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model'   => 'llama3',
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => ['temperature' => 0.6],
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray();
            $keywords = trim($data['response'] ?? '');

            if (strlen($keywords) > 5 && strpos($keywords, ' ') !== false) {
                return $keywords;
            }

            return $nom . ' tunisia travel landscape';

        } catch (\Exception $e) {
            return $nom . ' tunisia travel landscape';
        }
    }

    /**
     * Génère une description pour un voyage
     *
     * @param list<array<string, mixed>> $exemples
     */
    public function generateDescription(string $nom, array $exemples = []): string
    {
        $exemplesText = '';
        if (!empty($exemples)) {
            $exemplesText = "Voici des exemples de descriptions existantes (à utiliser comme inspiration mais sans copier):\n";
            foreach ($exemples as $i => $exemple) {
                $exemplesText .= ($i + 1) . ". " . ($exemple['description'] ?? '') . "\n";
            }
            $exemplesText .= "\n";
        }

        $prompt = <<<PROMPT
Tu es un rédacteur expert en voyages touristiques en Tunisie.

{$exemplesText}
Rédige une description touristique attrayante pour le voyage suivant: "{$nom}" en Tunisie.

RÈGLES:
- Longueur: 100-150 mots maximum
- Ton: professionnel, engageant, inspirant
- Mentionne: attractions, expériences uniques, patrimoine culturel
- Utilise des émojis pertinents (🌊, 🏛️, 🏜️, 🍽️, etc.)
- Pas de tirets, puces ou astérisques au début des lignes
- Pas de titres comme "Description:" ou "Résumé"
- Réponse UNIQUEMENT en français, sous forme de paragraphes fluides
PROMPT;

        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model'   => 'llama3',
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => [
                        'temperature' => 0.7,
                        'top_p'       => 0.9,
                    ],
                ],
                'timeout' => 25,
            ]);

            $data = $response->toArray();
            $description = trim($data['response'] ?? '');

            if (strlen($description) > 50) {
                return $description;
            }

            return $this->getDefaultDescription($nom);

        } catch (\Exception $e) {
            return $this->getDefaultDescription($nom);
        }
    }

    /**
     * Description par défaut si Ollama est indisponible
     */
    private function getDefaultDescription(string $nom): string
    {
        return "🌍 Découvrez {$nom} en Tunisie, une destination exceptionnelle qui allie culture, 
        patrimoine et paysages magnifiques. Laissez-vous séduire par son authenticité et vivez 
        une expérience unique au cœur de la Méditerranée. 🏖️🏛️";
    }

    /**
     * Génère une analyse globale (insight) à partir des données de programmes.
     *
     * @param list<array<string, mixed>> $programmesData
     * @return string
     */
    public function generateAlertInsights(array $programmesData): string
    {
        if (empty($programmesData)) {
            return '';
        }

        $resume = json_encode($programmesData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Tu es un conseiller expert en gestion de voyages et programmes touristiques en Tunisie.
Voici les données actuelles des programmes (réservations, taux d'occupation, paiements) :

{$resume}

En 3-4 phrases maximum, donne une analyse concise et des recommandations prioritaires pour améliorer
les performances globales. Réponds directement en français, sans introduction ni liste.
PROMPT;

        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model'   => 'llama3',
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => ['temperature' => 0.5],
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();
            return trim($data['response'] ?? '');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Génère un rapport financier hebdomadaire complet.
     * Méthode utilisée par AIReportGenerator.
     *
     * @param array<string, mixed> $reportData
     * @return string
     */
    public function generateFinancialReport(array $reportData): string
    {
        if (empty($reportData)) {
            return $this->getDefaultFallbackReport();
        }

        $jsonData = json_encode($reportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Tu es un analyste financier expert dans le secteur du tourisme en Tunisie.
Analyse les données de performances hebdomadaires suivantes et génère un RAPPORT COMPLET en français.

📊 DONNÉES DU RAPPORT HEBDOMADAIRE :
{$jsonData}

📝 STRUCTURE OBLIGATOIRE DU RAPPORT (respecte exactement ce format) :

📈 RÉSUMÉ EXÉCUTIF
(1 paragraphe de 2-3 phrases avec les KPI clés : voyages actifs, participants, revenus, taux d'occupation)

✅ POINTS FORTS ET SUCCÈS
• (3-4 points sur les meilleures performances, voyages les plus rentables, taux d'occupation élevés)
• (Ajoute des chiffres spécifiques et pourcentages)

⚠️ AXES D'AMÉLIORATION ET DÉFIS
• (3-4 points sur les voyages sous-performants, paiements en attente, faibles taux d'occupation)
• (Sois précis et constructif)

💡 RECOMMANDATIONS STRATÉGIQUES PRIORITAIRES
1. (Action concrète avec justification basée sur les données)
2. (Action marketing ou opérationnelle spécifique)
3. (Action pour améliorer la rentabilité ou l'occupation)
4. (Action pour réduire les paiements en attente ou augmenter les réservations)

🗓️ PERSPECTIVES POUR LA SEMAINE PROCHAINE
(1 paragraphe de recommandations anticipatives)

RÈGLES :
- Sois précis, chiffré et professionnel
- Utilise le dinar tunisien (DT) pour les montants
- Ne mentionne pas que tu es une IA, parle comme un analyste humain
- Réponse uniquement en français
- Maximum 400-500 mots
PROMPT;

        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model'   => 'llama3',
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => [
                        'temperature' => 0.5,
                        'top_p'       => 0.9,
                        'max_tokens'  => 1000,
                    ],
                ],
                'timeout' => 45,
            ]);

            $data = $response->toArray();
            $report = trim($data['response'] ?? '');

            if (strlen($report) < 100) {
                return $this->getDefaultFallbackReport();
            }

            return $report;

        } catch (\Exception $e) {
            error_log('Erreur Ollama generateFinancialReport: ' . $e->getMessage());
            return $this->getDefaultFallbackReport();
        }
    }

    /**
     * Version simplifiée de generate pour compatibilité avec l'appel dans AIReportGenerator.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function generate(array $data): string
    {
        return $this->generateFinancialReport($data);
    }

    /**
     * Rapport de fallback si Ollama est indisponible.
     *
     * @return string
     */
    private function getDefaultFallbackReport(): string
    {
        return "📈 RÉSUMÉ EXÉCUTIF\n" .
               "Cette semaine, l'activité touristique montre une performance à optimiser avec un taux d'occupation moyen nécessitant une attention particulière.\n\n" .
               "✅ POINTS FORTS ET SUCCÈS\n" .
               "• Structure tarifaire adaptée au marché local\n" .
               "• Diversité des offres disponibles\n" .
               "• Processus de réservation opérationnel\n\n" .
               "⚠️ AXES D'AMÉLIORATION ET DÉFIS\n" .
               "• Taux de conversion à renforcer\n" .
               "• Communication digitale à intensifier\n" .
               "• Fidélisation client à développer\n\n" .
               "💡 RECOMMANDATIONS STRATÉGIQUES PRIORITAIRES\n" .
               "1. Lancer une campagne de rappel pour les paiements en attente\n" .
               "2. Proposer des offres early bird pour stimuler les réservations\n" .
               "3. Optimiser les fiches voyages les moins performantes\n" .
               "4. Mettre en place un système d'alertes pour les faibles réservations\n\n" .
               "🗓️ PERSPECTIVES POUR LA SEMAINE PROCHAINE\n" .
               "Renforcer la présence sur les réseaux sociaux et activer des promotions ciblées pour améliorer les taux d'occupation.";
    }

    /**
     * Génère du contenu pour une ville spécifique.
     *
     * @param string $ville
     * @return array{suggestions: list<array{titre: string, description: string, tags: list<string>, image_url: string|null, video_url: string|null, ville: string, contexte_ia: string}>}
     */
    public function generateContentForVille(string $ville): array
    {
        $villeLower   = strtolower(trim($ville));
        $villeDisplay = ucfirst($villeLower);

        $analyseIA = $this->analyserVilleAvecIA($villeDisplay);

        $video = $this->mediaService->chercherVideoIntelligente($villeLower, $analyseIA['mots_cles_video']);

        $images = $this->mediaService->chercherImagesIntelligentes(
            $villeLower,
            $analyseIA['mots_cles_images']
        );

        $suggestionsTexte = $this->genererTextes($villeDisplay, $analyseIA);

        $result      = [];
        $imagesCount = count($images);

        foreach ($suggestionsTexte as $i => $texte) {
            $result[] = [
                'titre'       => $texte['titre'],
                'description' => $texte['description'],
                'tags'        => $texte['tags'],
                'image_url'   => $imagesCount > 0 ? ($images[$i % $imagesCount] ?? $images[0] ?? null) : null,
                'video_url'   => $i === 0 ? $video : null,
                'ville'       => $villeDisplay,
                'contexte_ia' => $analyseIA['contexte'],
            ];
        }

        return ['suggestions' => $result];
    }

    /**
     * @param string $ville
     * @return array{
     *     mots_cles_images: list<string>,
     *     mots_cles_video: string,
     *     contexte: string,
     *     spots_emblematiques: list<string>
     * }
     */
    private function analyserVilleAvecIA(string $ville): array
    {
        $prompt = <<<PROMPT
Tu es un expert en tourisme tunisien et en référencement d'images. Analyse la ville "{$ville}".

Réponds UNIQUEMENT en JSON valide:
{
  "mots_cles_images": ["mot1 anglais", "mot2 anglais", "mot3 anglais"],
  "mots_cles_video": "expression anglaise simple pour YouTube",
  "contexte": "description courte de ce qui rend cette ville unique",
  "spots_emblematiques": ["lieu1", "lieu2", "lieu3"]
}

Règles:
- mots_cles_images: tableau de 3 mots-clés EN ANGLAIS
- mots_cles_video: UNE SEULE STRING EN ANGLAIS - PAS UN TABLEAU
- contexte: 5-6 mots maximum
PROMPT;

        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model'   => 'llama3',
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => ['temperature' => 0.7],
                ],
                'timeout' => 20,
            ]);

            $data = $response->toArray();
            $text = $data['response'] ?? '';

            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $json = json_decode($matches[0], true);
                if (is_array($json)) {
                    $motsClesImages     = (array) ($json['mots_cles_images'] ?? [$ville, 'tunisia', 'landscape']);
                    $motsClesVideo      = (string) ($json['mots_cles_video'] ?? "{$ville} tunisia travel");
                    $contexte           = (string) ($json['contexte'] ?? "Ville tunisienne");
                    $spotsEmblematiques = (array) ($json['spots_emblematiques'] ?? []);

                    return [
                        'mots_cles_images'    => array_values(array_slice($motsClesImages, 0, 5)),
                        'mots_cles_video'     => $motsClesVideo,
                        'contexte'            => $contexte,
                        'spots_emblematiques' => array_values(array_slice($spotsEmblematiques, 0, 10)),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fallback silencieux
        }

        return [
            'mots_cles_images'    => [$ville, 'tunisia', 'medina'],
            'mots_cles_video'     => "{$ville} tunisia travel",
            'contexte'            => "Destination tunisienne",
            'spots_emblematiques' => [],
        ];
    }

    /**
     * @param string $ville
     * @param array<string, mixed> $analyse
     * @return list<array{titre: string, description: string, tags: list<string>}>
     */
    private function genererTextes(string $ville, array $analyse): array
    {
        $contexte = isset($analyse['contexte']) && is_string($analyse['contexte'])
            ? $analyse['contexte']
            : 'Destination authentique';

        return [
            [
                'titre'       => "📍 {$ville} : " . $contexte,
                'description' => "Découvrez {$ville} et ses trésors cachés. Une immersion authentique dans la culture tunisienne.",
                'tags'        => ['voyage', 'tunisie', strtolower(str_replace(' ', '', $ville))],
            ],
            [
                'titre'       => "☀️ Les secrets de {$ville} révélés",
                'description' => "Guide complet pour explorer {$ville}. Entre traditions locales et paysages spectaculaires.",
                'tags'        => ['guide', 'secrets', 'tunisie'],
            ],
            [
                'titre'       => "✨ Week-end magique à {$ville}",
                'description' => "Itinéraire de 48h à {$ville} : les meilleures adresses et couchers de soleil inoubliables.",
                'tags'        => ['weekend', 'tunisie', 'lifestyle'],
            ],
        ];
    }
}