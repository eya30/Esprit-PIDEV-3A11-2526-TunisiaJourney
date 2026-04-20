<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;

class GeminiAIService
{
    private $apiKey;
    private $client;
    private $connection;

    public function __construct(string $apiKey, Connection $connection)
    {
        $this->apiKey = $apiKey;
        $this->connection = $connection;
        $this->client = new Client([
            'timeout' => 30
        ]);
    }

    public function ask(string $question): array
    {
        // Si pas de clé API, utiliser le fallback
        if (empty($this->apiKey) || $this->apiKey === 'your_gemini_api_key_here') {
            return $this->getFallbackAnswer($question);
        }
        
        try {
            $response = $this->client->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->apiKey}", [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $this->getPrompt($question)]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                    ]
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Désolé, je n\'ai pas pu générer une réponse.';
            
            return [
                'success' => true,
                'answer' => $answer
            ];
        } catch (\Exception $e) {
            error_log('Gemini API Error: ' . $e->getMessage());
            return $this->getFallbackAnswer($question);
        }
    }
    
    private function getPrompt($question): string
    {
        return "Tu es un assistant vocal expert de la Tunisie pour TunisiaJourney. Réponds en français de façon naturelle et chaleureuse.
        
        Tu connais parfaitement :
        - Les voyages et destinations (Djerba, Hammamet, Carthage, Sidi Bou Saïd, Tozeur, Douz, El Jem)
        - Les plats tunisiens (couscous, brik, lablabi, merguez, chakchouka)
        - La culture, la météo, les hébergements, les transports
        - Comment réserver sur TunisiaJourney
        
        Question: " . $question;
    }
    
    private function getFallbackAnswer($question): array
    {
        $questionLower = strtolower($question);
        
        // Base de connaissances locale
        $reponses = [
            'bonjour' => "Bonjour ! Je suis votre assistant TunisiaJourney. Que voulez-vous savoir sur la Tunisie ?",
            'salut' => "Salut ! Ravi de vous parler ! Je connais tout sur la Tunisie.",
            'sidi bou said' => "Sidi Bou Saïd est un magnifique village blanc et bleu aux portes de Tunis ! Célèbre pour ses ruelles fleuries, ses portes bleues, et sa vue imprenable sur la mer. Ne manquez pas le Café des Nattes !",
            'djerba' => "Djerba est une île paradisiaque du sud tunisien ! Connue pour ses plages de sable fin, la Ghriba, et son climat doux toute l'année.",
            'carthage' => "Carthage est un site archéologique exceptionnel ! Ancienne cité punique puis romaine, classée à l'UNESCO.",
            'couscous' => "Le couscous est le plat national tunisien ! À base de semoule, légumes et viande. Un délice !",
            'voyage' => "Nous avons de magnifiques voyages en Tunisie : Djerba, Hammamet, Carthage, Sidi Bou Saïd, et bien d'autres ! Les prix commencent à partir de 300 DT.",
            'prix' => "Les prix de nos voyages varient entre 300 et 1500 DT par personne. En moyenne, comptez 600 DT pour une semaine.",
            'réservation' => "Pour réserver, choisissez votre destination sur notre site, remplissez le formulaire, et confirmez. C'est très simple !",
        ];
        
        foreach ($reponses as $key => $answer) {
            if (strpos($questionLower, $key) !== false) {
                return ['success' => true, 'answer' => $answer];
            }
        }
        
        return ['success' => true, 'answer' => "Merci pour votre question ! Je suis votre expert Tunisie. Je peux vous parler des voyages, des plats comme le couscous, des lieux comme Sidi Bou Saïd ou Djerba, des prix, et comment réserver. Que souhaitez-vous savoir exactement ?"];
    }
}