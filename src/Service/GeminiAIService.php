<?php

namespace App\Service;

<<<<<<< HEAD
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GeminiAIService
{
    private string $apiKey;
    private Client $client;

    /**
     * @param string $apiKey Clé API Google Gemini
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // Optionnel: désactiver la vérification SSL en développement
        ]);
    }

    /**
     * Pose une question à l'assistant IA
     *
     * @param string $question La question posée par l'utilisateur
     * @return array{success: bool, answer: string}
     */
=======
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

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
    
<<<<<<< HEAD
    /**
     * Génère le prompt pour l'API Gemini
     *
     * @param string $question La question de l'utilisateur
     * @return string Le prompt formaté
     */
    private function getPrompt(string $question): string
=======
    private function getPrompt($question): string
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    {
        return "Tu es un assistant vocal expert de la Tunisie pour TunisiaJourney. Réponds en français de façon naturelle et chaleureuse.
        
        Tu connais parfaitement :
        - Les voyages et destinations (Djerba, Hammamet, Carthage, Sidi Bou Saïd, Tozeur, Douz, El Jem)
        - Les plats tunisiens (couscous, brik, lablabi, merguez, chakchouka)
        - La culture, la météo, les hébergements, les transports
        - Comment réserver sur TunisiaJourney
        
        Question: " . $question;
    }
    
<<<<<<< HEAD
    /**
     * Réponse de fallback quand l'API Gemini n'est pas disponible
     *
     * @param string $question La question de l'utilisateur
     * @return array{success: bool, answer: string}
     */
    private function getFallbackAnswer(string $question): array
=======
    private function getFallbackAnswer($question): array
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
            'brik' => "Le brik est une délicieuse beignet tunisien à l'œuf, au thon et aux câpres. Un incontournable de l'apéro !",
            'lablabi' => "Le lablabi est une soupe de pois chiches épicée, parfaite pour l'hiver. À déguster avec des morceaux de pain !",
            'voyage' => "Nous avons de magnifiques voyages en Tunisie : Djerba, Hammamet, Carthage, Sidi Bou Saïd, et bien d'autres ! Les prix commencent à partir de 300 DT.",
            'prix' => "Les prix de nos voyages varient entre 300 et 1500 DT par personne. En moyenne, comptez 600 DT pour une semaine.",
            'réservation' => "Pour réserver, choisissez votre destination sur notre site, remplissez le formulaire, et confirmez. C'est très simple !",
            'reservation' => "Pour réserver, choisissez votre destination sur notre site, remplissez le formulaire, et confirmez. C'est très simple !",
            'tarif' => "Les prix de nos voyages varient entre 300 et 1500 DT par personne. En moyenne, comptez 600 DT pour une semaine.",
            'promotion' => "Nous avons régulièrement des promotions ! Consultez notre site pour les offres actuelles.",
            'hammamet' => "Hammamet est la perle du Cap Bon ! Connue pour ses plages magnifiques, sa médina et ses jardins de cactus.",
            'tozeur' => "Tozeur est la porte du désert ! Célèbre pour sa palmeraie, son architecture de briques et les gorges de Mides.",
            'douz' => "Douz est la porte du Sahara ! Célèbre pour son festival international du désert et les balades à dos de dromadaire.",
            'el jem' => "El Jem abrite l'un des plus grands amphithéâtres romains du monde ! Classé à l'UNESCO.",
            'mahdia' => "Mahdia est une ville côtière historique avec sa célèbre skifa (porte fortifiée) et ses plages magnifiques.",
            'monastir' => "Monastir abrite le magnifique Ribat, une forteresse du 8ème siècle, et le mausolée de Habib Bourguiba.",
            'kairouan' => "Kairouan est la 4ème ville sainte de l'Islam, célèbre pour sa Grande Mosquée et ses tapis.",
            'tabarka' => "Tabarka est célèbre pour son festival de jazz et ses magnifiques paysages de corail et forêts.",
            'zarzis' => "Zarzis est une station balnéaire tranquille aux portes du désert, parfaite pour se ressourcer.",
            'météo' => "Le climat tunisien est méditerranéen au nord (étés chauds, hivers doux) et désertique au sud.",
            'hôtel' => "Nous travaillons avec les meilleurs hôtels de Tunisie allant du 3 au 5 étoiles, clubs et riads.",
            'transport' => "Vous pouvez voyager en Tunisie en louant une voiture, en prenant le train, le louage ou le taxi.",
        ];
        
        foreach ($reponses as $key => $answer) {
            if (str_contains($questionLower, $key)) {
=======
            'voyage' => "Nous avons de magnifiques voyages en Tunisie : Djerba, Hammamet, Carthage, Sidi Bou Saïd, et bien d'autres ! Les prix commencent à partir de 300 DT.",
            'prix' => "Les prix de nos voyages varient entre 300 et 1500 DT par personne. En moyenne, comptez 600 DT pour une semaine.",
            'réservation' => "Pour réserver, choisissez votre destination sur notre site, remplissez le formulaire, et confirmez. C'est très simple !",
        ];
        
        foreach ($reponses as $key => $answer) {
            if (strpos($questionLower, $key) !== false) {
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                return ['success' => true, 'answer' => $answer];
            }
        }
        
        return ['success' => true, 'answer' => "Merci pour votre question ! Je suis votre expert Tunisie. Je peux vous parler des voyages, des plats comme le couscous, des lieux comme Sidi Bou Saïd ou Djerba, des prix, et comment réserver. Que souhaitez-vous savoir exactement ?"];
    }
<<<<<<< HEAD

    /**
     * Vérifie si l'API Gemini est configurée
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'your_gemini_api_key_here';
    }

    /**
     * Obtient la version du modèle utilisé
     *
     * @return string
     */
    public function getModelVersion(): string
    {
        return 'gemini-pro';
    }

    /**
     * Teste la connexion à l'API Gemini
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API Gemini non configurée. Veuillez définir la clé API.'
            ];
        }

        try {
            $response = $this->client->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->apiKey}", [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Dis simplement "Connexion OK"']
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 50,
                    ]
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody(), true);
            $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'success' => true,
                'message' => 'Connexion à l\'API Gemini réussie ! Réponse: ' . $answer
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur de connexion: ' . $e->getMessage()
            ];
        }
    }
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
}