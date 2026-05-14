<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
<<<<<<< HEAD
use GuzzleHttp\Exception\GuzzleException;

class GeminiEAIService
{
    private string $apiKey;
    private Client $client;
    private Connection $connection;
=======

class GeminiEAIService
{
    private $apiKey;
    private $client;
    private $connection;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

    public function __construct(string $apiKey, Connection $connection)
    {
        $this->apiKey = $apiKey;
        $this->connection = $connection;
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'timeout' => 30
        ]);
    }

    private function getSystemPrompt(): string
    {
        return "Tu es un assistant virtuel pour TunisiaJourney, une agence de voyage spécialisée en Tunisie.
        
        Tu connais parfaitement tous les voyages, programmes, destinations et services proposés par TunisiaJourney.
        
        Règles à suivre :
        - Réponds toujours en français
        - Sois amical, chaleureux et professionnel
        - Donne des informations précises sur les voyages
        - Propose des alternatives si un voyage n'est pas disponible
        - Mentionne les prix en dinars tunisiens (DT)
        - Si tu ne sais pas répondre, propose de contacter le service client
        - Encourage l'utilisateur à réserver sur le site
        
        Tu peux donner des conseils de voyage, des informations sur les destinations, les meilleures périodes, etc.";
    }

<<<<<<< HEAD
    /**
     * @return array<int, array<string, mixed>>
     */
    private function getVoyages(): array
    {
        return $this->connection->fetchAllAssociative("
=======
    private function getTravelContext(): string
    {
        $voyages = $this->connection->fetchAllAssociative("
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            SELECT v.nom, v.description, v.prix, v.capacite, 
                   GROUP_CONCAT(p.nom SEPARATOR ', ') as programmes
            FROM voyages v
            LEFT JOIN programmes p ON p.idV = v.idV
            GROUP BY v.idV
            LIMIT 20
        ");
<<<<<<< HEAD
    }

    private function getTravelContext(): string
    {
        $voyages = $this->getVoyages();
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        
        $context = "Voici les voyages proposés par TunisiaJourney :\n\n";
        
        foreach ($voyages as $voyage) {
            $context .= "• {$voyage['nom']} : {$voyage['description']}\n";
            $context .= "  Prix : {$voyage['prix']} DT | Capacité : {$voyage['capacite']} personnes\n";
            if ($voyage['programmes']) {
                $context .= "  Programmes : {$voyage['programmes']}\n";
            }
            $context .= "\n";
        }
        
        return $context;
    }

<<<<<<< HEAD
    /**
     * @return array{success: bool, answer?: string, question?: string, error?: string, debug?: string}
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function ask(string $question): array
    {
        try {
            $context = $this->getTravelContext();
            
            $prompt = $this->getSystemPrompt() . "\n\n" . $context . "\n\nQuestion de l'utilisateur : " . $question;
            
<<<<<<< HEAD
            $response = $this->client->post("models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
=======
         $response = $this->client->post("models/gemini-2.0-flash:generateContent?key={$this->apiKey}",  [
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 800,
                        'topP' => 0.95
                    ]
                ]
            ]);
            
<<<<<<< HEAD
            $data = json_decode($response->getBody()->getContents(), true);
=======
            $data = json_decode($response->getBody(), true);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            
            $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Désolé, je n\'ai pas pu générer une réponse.';
            
            return [
                'success' => true,
                'answer' => $answer,
                'question' => $question
            ];
        } catch (\Exception $e) {
            error_log('Gemini API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Désolé, une erreur est survenue. Veuillez réessayer plus tard.',
                'debug' => $e->getMessage()
            ];
        }
    }
}