<?php
// src/Service/OllamaService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaService
{
    private $client;
    private MediaIntelligentService $mediaService;
    private string $ollamaUrl = 'http://localhost:11434/api/generate';

    public function __construct(HttpClientInterface $client, MediaIntelligentService $mediaService)
    {
        $this->client = $client;
        $this->mediaService = $mediaService;
    }

    public function generateContentForVille(string $ville): array
    {
        $villeLower = strtolower(trim($ville));
        $villeDisplay = ucfirst($villeLower);

        // 1. L'IA analyse la ville et génère des mots-clés de recherche spécifiques
        $analyseIA = $this->analyserVilleAvecIA($villeDisplay);
        
        // 2. Utiliser ces mots-clés pour chercher des médias réels
        // CORRECTION : mots_cles_video est un array, on prend le premier élément
        $video = $this->mediaService->chercherVideoIntelligente(
            $villeLower, 
            is_array($analyseIA['mots_cles_video']) 
                ? implode(' ', $analyseIA['mots_cles_video']) 
                : $analyseIA['mots_cles_video']
        );
        
        $images = $this->mediaService->chercherImagesIntelligentes(
            $villeLower, 
            $analyseIA['mots_cles_images']
        );

        // 3. Générer le contenu texte
        $suggestionsTexte = $this->genererTextes($villeDisplay, $analyseIA);

        // 4. Assembler tout
        $result = [];
        foreach ($suggestionsTexte as $i => $texte) {
            $result[] = [
                'titre' => $texte['titre'],
                'description' => $texte['description'],
                'tags' => $texte['tags'],
                'image_url' => $images[$i % count($images)] ?? $images[0],
                'video_url' => $i === 0 ? $video : null,
                'ville' => $villeDisplay,
                'contexte_ia' => $analyseIA['contexte']
            ];
        }

        return ['suggestions' => $result];
    }

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
- mots_cles_images: tableau de 3 mots-clés EN ANGLAIS (ex: ["djerba beach", "houmt souk", "tunisia island"])
- mots_cles_video: UNE SEULE STRING EN ANGLAIS (ex: "Djerba Tunisia travel vlog") - PAS UN TABLEAU
- contexte: 5-6 mots maximum
PROMPT;

        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model' => 'llama3',
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => ['temperature' => 0.7]
                ],
                'timeout' => 20
            ]);

            $data = $response->toArray();
            $text = $data['response'] ?? '';
            
            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $json = json_decode($matches[0], true);
                return [
                    'mots_cles_images' => $json['mots_cles_images'] ?? [$ville, 'tunisia', 'landscape'],
                    'mots_cles_video' => $json['mots_cles_video'] ?? "{$ville} tunisia travel",
                    'contexte' => $json['contexte'] ?? "Ville tunisienne",
                    'spots_emblematiques' => $json['spots_emblematiques'] ?? []
                ];
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return [
            'mots_cles_images' => [$ville, 'tunisia', 'medina'],
            'mots_cles_video' => "{$ville} tunisia travel",
            'contexte' => "Destination tunisienne",
            'spots_emblematiques' => []
        ];
    }

    private function genererTextes(string $ville, array $analyse): array
    {
        return [
            [
                'titre' => "📍 {$ville} : {$analyse['contexte']}",
                'description' => "Découvrez {$ville} et ses trésors cachés. Une immersion authentique dans la culture tunisienne.",
                'tags' => ['voyage', 'tunisie', strtolower(str_replace(' ', '', $ville))]
            ],
            [
                'titre' => "☀️ Les secrets de {$ville} révélés",
                'description' => "Guide complet pour explorer {$ville}. Entre traditions locales et paysages spectaculaires.",
                'tags' => ['guide', 'secrets', 'tunisie']
            ],
            [
                'titre' => "✨ Week-end magique à {$ville}",
                'description' => "Itinéraire de 48h à {$ville} : les meilleures adresses et couchers de soleil inoubliables.",
                'tags' => ['weekend', 'tunisie', 'lifestyle']
            ]
        ];
    }
}
