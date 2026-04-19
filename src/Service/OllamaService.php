<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class OllamaService
{
    private string $ollamaUrl;
    private string $model;
    private string $imageModel;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private bool $isAvailable;
    private bool $isImageModelAvailable;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $ollamaUrl = 'http://localhost:11434',
        string $model = 'llama3.2:3b',
        string $imageModel = 'llava:latest'
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->ollamaUrl = $ollamaUrl;
        $this->model = $model;
        $this->imageModel = $imageModel;
        $this->isAvailable = $this->checkAvailability();
        $this->isImageModelAvailable = $this->checkImageModelAvailability();
    }

    private function checkAvailability(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags', [
                'timeout' => 3,
            ]);
            $data = $response->toArray();
            $models = array_column($data['models'] ?? [], 'name');
            return in_array($this->model, $models);
        } catch (\Exception $e) {
            $this->logger->warning('Ollama non disponible: ' . $e->getMessage());
            return false;
        }
    }

    private function checkImageModelAvailability(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags', [
                'timeout' => 3,
            ]);
            $data = $response->toArray();
            $models = array_column($data['models'] ?? [], 'name');
            
            // Vérifier si le modèle existe (avec ou sans :latest)
            $modelFound = false;
            foreach ($models as $m) {
                if ($m === $this->imageModel || $m === str_replace(':latest', '', $this->imageModel)) {
                    $modelFound = true;
                    break;
                }
            }
            
            $this->logger->info('Modèles disponibles: ' . implode(', ', $models));
            $this->logger->info('Recherche modèle image: ' . $this->imageModel . ' - Trouvé: ' . ($modelFound ? 'OUI' : 'NON'));
            
            return $modelFound;
        } catch (\Exception $e) {
            $this->logger->warning('Modèle image non disponible: ' . $e->getMessage());
            return false;
        }
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function isImageModelAvailable(): bool
    {
        return $this->isImageModelAvailable;
    }

    public function generateDescription(string $nomVoyage, array $exemplesBDD = []): string
    {
        if (!$this->isAvailable) {
            return $this->fallbackDescription($nomVoyage);
        }

        $exemples = !empty($exemplesBDD) ? $exemplesBDD : $this->getDefaultExemples();

        $fewShotBlock = '';
        foreach (array_slice($exemples, 0, 3) as $ex) {
            $nom = trim($ex['nom'] ?? '');
            $desc = trim($ex['description'] ?? '');
            if ($nom && $desc) {
                $fewShotBlock .= "Exemple - Destination: {$nom}\nDescription: {$desc}\n\n";
            }
        }

        $prompt = <<<PROMPT
Tu es un expert en tourisme tunisien pour l'agence TunisiaJourney.

Voici des exemples de descriptions que tu as déjà écrites :

{$fewShotBlock}

Maintenant, écris une description pour cette destination : {$nomVoyage}

RÈGLES IMPORTANTES :
- Écris exactement 2 à 3 phrases courtes (maximum 80 mots)
- Style poétique et professionnel
- Mentionne des détails sensoriels (couleurs, odeurs, ambiances)
- Ne réponds qu'avec la description, rien d'autre
- Pas de titres, pas de listes, pas de markdown

Description :
PROMPT;

        try {
            $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/generate', [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.85,
                        'top_p' => 0.9,
                        'num_predict' => 150,
                    ],
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray();
            $description = trim($data['response'] ?? '');
            $description = $this->cleanDescription($description);

            if (empty($description)) {
                return $this->fallbackDescription($nomVoyage);
            }

            return $description;

        } catch (\Exception $e) {
            $this->logger->error('Erreur Ollama generateDescription: ' . $e->getMessage());
            return $this->fallbackDescription($nomVoyage);
        }
    }

    public function generateInsights(string $data, string $prompt): ?string
    {
        if (!$this->isAvailable()) {
            $this->logger->warning('Ollama non disponible pour generateInsights');
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/generate', [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $data . "\n\n" . $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'num_predict' => 800,
                    ],
                ],
                'timeout' => 60,
            ]);

            $responseData = $response->toArray();
            $result = $responseData['response'] ?? null;
            
            if ($result) {
                $this->logger->info('Insights générés avec succès');
            }
            
            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Erreur Ollama generateInsights: ' . $e->getMessage());
            return null;
        }
    }

    public function generateImageKeywords(string $query): ?string
    {
        if (!$this->isImageModelAvailable) {
            $this->logger->warning('Modèle image non disponible');
            return null;
        }

        try {
            $prompt = "Génère 5 mots-clés en anglais séparés par des virgules pour chercher des photos de voyage de: " . $query . " en Tunisie. 
                       Exemple pour Sidi Bou Said: 'blue and white houses, mediterranean sea, narrow streets, bougainvillea, traditional doors'
                       Retourne uniquement les mots-clés séparés par des virgules, rien d'autre.";
            
            $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/generate', [
                'json' => [
                    'model' => $this->imageModel,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'num_predict' => 100,
                    ],
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray();
            return trim($data['response'] ?? '');

        } catch (\Exception $e) {
            $this->logger->error('Erreur génération mots-clés: ' . $e->getMessage());
            return null;
        }
    }

    private function cleanDescription(string $description): string
    {
        $description = preg_replace('/\*+/', '', $description);
        $description = preg_replace('/#+/', '', $description);
        
        $lines = explode("\n", $description);
        $cleaned = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 10 && !preg_match('/^[A-Z\s]{10,}$/', $line)) {
                $cleaned[] = $line;
            }
        }
        
        $description = implode(' ', $cleaned);
        
        $sentences = preg_split('/(?<=[.!?])\s+(?=[A-ZÀ-Ý])/', $description);
        if (count($sentences) > 3) {
            $description = implode(' ', array_slice($sentences, 0, 3));
        }
        
        return trim($description);
    }

    private function getDefaultExemples(): array
    {
        return [
            [
                'nom' => 'Sidi Bou Said',
                'description' => 'Perché sur sa falaise blanche surplombant la Méditerranée, Sidi Bou Saïd est un village de carte postale. Ses ruelles pavées bordées de maisons bleues et blanches vous invitent à la flânerie.'
            ],
            [
                'nom' => 'Djerba',
                'description' => 'L\'île aux mille palmiers vous accueille dans un écrin de lumière méditerranéenne. Entre plages de sable fin et marchés colorés, Djerba est un havre de paix authentique.'
            ],
            [
                'nom' => 'Le Sahara tunisien',
                'description' => 'La porte du désert s\'ouvre sur des étendues dorées à perte de vue. À dos de dromadaire au coucher du soleil, vous comprendrez pourquoi les nomades ont aimé ces espaces infinis.'
            ],
        ];
    }

    private function fallbackDescription(string $nom): string
    {
        $templates = [
            "Découvrez $nom, une destination exceptionnelle au cœur de la Tunisie. Entre paysages à couper le souffle et traditions millénaires, ce voyage vous promet des souvenirs inoubliables.",
            "Partez à l'aventure à $nom et plongez au cœur de la Tunisie authentique. Des expériences uniques vous attendent dans ce cadre préservé.",
            "Bienvenue à $nom, où la magie tunisienne opère dès votre arrivée. Entre culture riche et hospitalité légendaire, préparez-vous à vivre des moments uniques.",
        ];
        return $templates[array_rand($templates)];
    }
}