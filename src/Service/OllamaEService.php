<?php
// src/Service/OllamaService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class OllamaEService
{
    private const OLLAMA_URL = 'http://localhost:11434/api/generate';
    private const MODEL = 'llama3';
    private const TIMEOUT = 15;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {}

    /**
     * Méthode principale qui retourne météo + trafic + prédiction
<<<<<<< HEAD
     *
     * @return array<string, mixed>
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    public function getFullPrediction(
        float  $distanceKm,
        float  $progression,
        string $statut,
        float  $lat,
        float  $lng,
        string $adresseDest,
        string $ville
    ): array {
        $distRestante = round($distanceKm * (1 - $progression / 100), 1);
        $heure = (int)date('H');
        $jourSemaine = $this->getJourSemaine();
<<<<<<< HEAD

=======
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $prompt = <<<PROMPT
Tu es un expert en logistique et trafic routier en Tunisie.

📍 POSITION : {$ville}, Tunisie (latitude {$lat}, longitude {$lng})
📅 DATE : " . date('d/m/Y') . " ({$jourSemaine})
🕐 HEURE : " . date('H:i') . "

DONNÉES COMMANDE :
- Statut : {$statut}
- Distance restante : {$distRestante} km
- Progression : {$progression}%

🌍 RÈGLES TRAFIC TUNIS (CONNAISSANCES RÉELLES) :

HORAIRES DE POINTE À TUNIS :
- 7h30 - 9h30 : HEURES DE POINTE (embouteillages sévères)
- 12h00 - 14h00 : HEURE DE PAUSE (trafic modéré)
- 16h30 - 19h00 : HEURES DE POINTE (retour du travail)
- 20h00 - 6h00 : NUIT (trafic fluide)

JOURS SPÉCIAUX :
- Lundi matin : trafic TRÈS ÉLEVÉ (retour weekend)
- Vendredi soir : trafic TRÈS ÉLEVÉ (départ weekend)
- Samedi/Dimanche : trafic FAIBLE (sauf centres commerciaux)

ZONES SENSIBLES À TUNIS :
- Avenue Habib Bourguiba : embouteillages fréquents
- Tunis - La Marsa : dense aux heures de pointe
- Tunis - Ben Arous : trafic intense
- Aéroport Tunis-Carthage : variable selon vols

RÈGLES MÉTÉO TUNIS (réalistes) :
- Été (juin-sept) : 35-40°C, ensoleillé
- Hiver (nov-fév) : 10-18°C, parfois pluie
- Printemps/Automne : 20-28°C, agréable

RÈGLES ESTIMATION LIVRAISON :
- Vitesse base : 40 km/h
- Trafic ÉLEVÉ : vitesse -40%
- Trafic MOYEN : vitesse -20%
- Pluie/Orage : vitesse -30%

RÉPONDS UNIQUEMENT EN JSON (sans texte avant/après) :
{
    "meteo": {
        "label": "Ensoleillé|Nuageux|Pluie|Orage|Brumeux",
        "icon": "☀️|☁️|🌧️|⛈️|🌫️",
        "temp": "XX°C",
        "color": "#code_couleur_hex",
        "impact": "normal|lent"
    },
    "trafic": {
        "label": "Fluide|Normal|Modéré|Dense|Bloqué",
        "icon": "🟢|🟡|🟠|🔴|⛔",
        "color": "#code_couleur_hex",
        "detail": "explication courte du trafic à {$ville}",
        "vitesse_reduite": 0.0
    },
    "livraison": {
        "eta_hours": 0.0,
        "message": "message personnalisé incluant la raison (ex: 'Trafic dense sur l'avenue...')"
    }
}

Utilise TES CONNAISSANCES RÉELLES de la circulation à {$ville} en Tunisie.
Sois PRÉCIS et RÉALISTE. Si c'est l'heure de pointe, dis-le. Si c'est calme, dis-le.
PROMPT;

        try {
            $response = $this->client->request('POST', self::OLLAMA_URL, [
                'timeout' => self::TIMEOUT,
                'json' => [
                    'model' => self::MODEL,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => [
                        'temperature' => 0.3,
                        'num_predict' => 350,
                    ],
                ],
            ]);

            $body = $response->toArray();
            $raw = $body['response'] ?? '{}';
<<<<<<< HEAD

=======
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            return $this->parseFullResponse($raw, $distanceKm, $progression, $statut, $ville);

        } catch (\Throwable $e) {
            $this->logger->warning('[Ollama] Indisponible: ' . $e->getMessage());
            return $this->fallbackFull($distanceKm, $progression, $statut, $ville);
        }
    }

    private function getJourSemaine(): string
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        return $jours[(int)date('N') - 1];
    }

<<<<<<< HEAD
    /**
     * @return array<string, mixed>
     */
    private function parseFullResponse(string $raw, float $dist, float $prog, string $statut, string $ville): array
    {
        $clean = trim((string) preg_replace('/```(?:json)?/i', '', $raw));
=======
    private function parseFullResponse(string $raw, float $dist, float $prog, string $statut, string $ville): array
    {
        $clean = trim(preg_replace('/```(?:json)?/i', '', $raw));
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $data = json_decode($clean, true);

        if (is_array($data) && isset($data['meteo']) && isset($data['trafic']) && isset($data['livraison'])) {
            return [
                'meteo' => [
<<<<<<< HEAD
                    'label'  => $data['meteo']['label'] ?? 'Ensoleillé',
                    'icon'   => $data['meteo']['icon'] ?? '☀️',
                    'temp'   => $data['meteo']['temp'] ?? '24°C',
                    'color'  => $data['meteo']['color'] ?? '#F59E0B',
                    'impact' => $data['meteo']['impact'] ?? 'normal',
                ],
                'trafic' => [
                    'label'          => $data['trafic']['label'] ?? 'Normal',
                    'icon'           => $data['trafic']['icon'] ?? '🟡',
                    'color'          => $data['trafic']['color'] ?? '#F59E0B',
                    'detail'         => $data['trafic']['detail'] ?? "Trafic normal à $ville",
=======
                    'label' => $data['meteo']['label'] ?? 'Ensoleillé',
                    'icon' => $data['meteo']['icon'] ?? '☀️',
                    'temp' => $data['meteo']['temp'] ?? '24°C',
                    'color' => $data['meteo']['color'] ?? '#F59E0B',
                    'impact' => $data['meteo']['impact'] ?? 'normal',
                ],
                'trafic' => [
                    'label' => $data['trafic']['label'] ?? 'Normal',
                    'icon' => $data['trafic']['icon'] ?? '🟡',
                    'color' => $data['trafic']['color'] ?? '#F59E0B',
                    'detail' => $data['trafic']['detail'] ?? "Trafic normal à $ville",
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                    'vitesse_reduite' => $data['trafic']['vitesse_reduite'] ?? 0.0,
                ],
                'ia' => [
                    'eta_hours' => max(0, (float)($data['livraison']['eta_hours'] ?? 0)),
<<<<<<< HEAD
                    'message'   => substr($data['livraison']['message'] ?? 'Livraison en cours', 0, 180),
                    'source'    => 'ollama',
=======
                    'message' => substr($data['livraison']['message'] ?? 'Livraison en cours', 0, 180),
                    'source' => 'ollama',
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                ],
            ];
        }

        return $this->fallbackFull($dist, $prog, $statut, $ville);
    }

<<<<<<< HEAD
    /**
     * @return array<string, mixed>
     */
    private function fallbackFull(float $dist, float $prog, string $statut, string $ville): array
    {
        $heure = (int)date('H');
        $jour  = (int)date('N');
        $estWeekend = ($jour >= 6);

        if ($estWeekend) {
            $trafic = ['label' => 'Fluide',  'icon' => '🟢', 'color' => '#10B981', 'detail' => "Weekend calme à $ville",           'vitesse_reduite' => 0.0];
        } elseif ($heure >= 7 && $heure <= 9) {
            $trafic = ['label' => 'Dense',   'icon' => '🔴', 'color' => '#EF4444', 'detail' => "Heure de pointe matinale à $ville", 'vitesse_reduite' => 0.4];
        } elseif ($heure >= 17 && $heure <= 19) {
            $trafic = ['label' => 'Dense',   'icon' => '🔴', 'color' => '#EF4444', 'detail' => "Heure de pointe du soir à $ville",  'vitesse_reduite' => 0.4];
        } elseif ($heure >= 12 && $heure <= 14) {
            $trafic = ['label' => 'Modéré',  'icon' => '🟠', 'color' => '#F59E0B', 'detail' => "Pause déjeuner, trafic modéré",    'vitesse_reduite' => 0.2];
        } elseif ($heure >= 22 || $heure <= 5) {
            $trafic = ['label' => 'Fluide',  'icon' => '🟢', 'color' => '#10B981', 'detail' => "Circulation nocturne fluide",       'vitesse_reduite' => 0.0];
        } else {
            $trafic = ['label' => 'Normal',  'icon' => '🟡', 'color' => '#F59E0B', 'detail' => "Trafic normal",                    'vitesse_reduite' => 0.1];
        }

=======
    private function fallbackFull(float $dist, float $prog, string $statut, string $ville): array
    {
        $heure = (int)date('H');
        $jour = (int)date('N');
        $estWeekend = ($jour >= 6);
        
        if ($estWeekend) {
            $trafic = ['label' => 'Fluide', 'icon' => '🟢', 'color' => '#10B981', 'detail' => "Weekend calme à $ville", 'vitesse_reduite' => 0.0];
        } elseif ($heure >= 7 && $heure <= 9) {
            $trafic = ['label' => 'Dense', 'icon' => '🔴', 'color' => '#EF4444', 'detail' => "Heure de pointe matinale à $ville", 'vitesse_reduite' => 0.4];
        } elseif ($heure >= 17 && $heure <= 19) {
            $trafic = ['label' => 'Dense', 'icon' => '🔴', 'color' => '#EF4444', 'detail' => "Heure de pointe du soir à $ville", 'vitesse_reduite' => 0.4];
        } elseif ($heure >= 12 && $heure <= 14) {
            $trafic = ['label' => 'Modéré', 'icon' => '🟠', 'color' => '#F59E0B', 'detail' => "Pause déjeuner, trafic modéré", 'vitesse_reduite' => 0.2];
        } elseif ($heure >= 22 || $heure <= 5) {
            $trafic = ['label' => 'Fluide', 'icon' => '🟢', 'color' => '#10B981', 'detail' => "Circulation nocturne fluide", 'vitesse_reduite' => 0.0];
        } else {
            $trafic = ['label' => 'Normal', 'icon' => '🟡', 'color' => '#F59E0B', 'detail' => "Trafic normal", 'vitesse_reduite' => 0.1];
        }
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $month = (int)date('n');
        if ($month >= 6 && $month <= 9) {
            $meteo = ['label' => 'Ensoleillé', 'icon' => '☀️', 'temp' => '32°C', 'color' => '#F59E0B', 'impact' => 'normal'];
        } elseif ($month >= 11 || $month <= 2) {
<<<<<<< HEAD
            $meteo = ['label' => 'Nuageux',    'icon' => '☁️', 'temp' => '14°C', 'color' => '#6B7280', 'impact' => 'normal'];
        } else {
            $meteo = ['label' => 'Ensoleillé', 'icon' => '☀️', 'temp' => '24°C', 'color' => '#F59E0B', 'impact' => 'normal'];
        }

        $distRestante = $dist * (1 - $prog / 100);
        $vitesse      = 40 * (1 - $trafic['vitesse_reduite']);
        $etaH         = $distRestante / $vitesse;
        $message      = $trafic['detail'] . ". Livraison estimée dans " . round($etaH * 60) . " minutes.";

        return [
            'meteo'  => $meteo,
            'trafic' => $trafic,
            'ia'     => [
                'eta_hours' => round($etaH, 2),
                'message'   => $message,
                'source'    => 'fallback',
=======
            $meteo = ['label' => 'Nuageux', 'icon' => '☁️', 'temp' => '14°C', 'color' => '#6B7280', 'impact' => 'normal'];
        } else {
            $meteo = ['label' => 'Ensoleillé', 'icon' => '☀️', 'temp' => '24°C', 'color' => '#F59E0B', 'impact' => 'normal'];
        }
        
        $distRestante = $dist * (1 - $prog / 100);
        $vitesse = 40 * (1 - $trafic['vitesse_reduite']);
        $etaH = $distRestante / $vitesse;
        
        $message = $trafic['detail'] . ". Livraison estimée dans " . round($etaH * 60) . " minutes.";
        
        return [
            'meteo' => $meteo,
            'trafic' => $trafic,
            'ia' => [
                'eta_hours' => round($etaH, 2),
                'message' => $message,
                'source' => 'fallback',
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            ],
        ];
    }

<<<<<<< HEAD
    /**
     * Compatibilité avec l'ancienne méthode
     *
     * @return array<string, mixed>
     */
    public function getDeliveryPrediction(
        float  $distanceKm,
        float  $progression,
=======
    // Compatibilité avec l'ancienne méthode
    public function getDeliveryPrediction(
        float $distanceKm,
        float $progression,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        string $statut,
        string $meteo,
        string $trafic,
        string $adresseDest
    ): array {
        $result = $this->getFullPrediction(
            $distanceKm,
            $progression,
            $statut,
            36.8065,
            10.1815,
            $adresseDest,
            'Tunis'
        );
        return $result['ia'];
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
