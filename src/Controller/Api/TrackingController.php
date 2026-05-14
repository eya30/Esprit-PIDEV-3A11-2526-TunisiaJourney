<?php
// src/Controller/Api/TrackingController.php

namespace App\Controller\Api;

use App\Repository\CommandeRepository;
use App\Service\OllamaEService;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TrackingController extends AbstractController
{
    private const DEPART = [
        'lat'   => 36.8190,
        'lng'   => 10.1658,
        'label' => 'Entrepôt TunisiaJourney — Tunis',
    ];

    private const TRAJET_DUREE = 60;

    public function __construct(
        private CacheInterface         $cache,
        private OllamaEService         $ollama,
        private EntityManagerInterface $em,
        private WhatsAppService        $whatsApp,
    ) {}

    #[Route('/api/tracking/{id}', name: 'api_tracking', methods: ['GET'])]
    public function track(int $id, CommandeRepository $repo): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $commande = $repo->find($id);
        if (!$commande) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }
        if ($commande->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $statut      = $commande->getStatut() ?? 'En attente';
        $adresseDest = $commande->getAdresseLiv() ?? 'Tunis, Tunisie';
        $dateC       = $commande->getDateC();

        $coordsDest  = $this->geocode($adresseDest);
        $progression = $this->getProgression($id, $statut);

        if ($progression >= 99.5 && $statut !== 'Livrée' && $statut !== 'Annulée') {
            $commande->setStatut('Livrée');
            $this->em->flush();

            $this->cache->delete('tracking_start_' . $id);

            $user      = $commande->getUser();
            $telephone = $user->getTelephone(); // -> au lieu de ?-> : $user est non-nullable ici

            if ($telephone !== null) {
                $this->whatsApp->sendLivreurArrive(
                    $telephone,
                    $user->getNom()    ?? '',
                    $user->getPrenom() ?? '',
                    $commande->getId() ?? 0
                );
            }

            $progression = 100.0;
            $statut      = 'Livrée';
        }

        $distanceKm = $this->haversine(
            self::DEPART['lat'], self::DEPART['lng'],
            $coordsDest['lat'],  $coordsDest['lng']
        );

        $t       = $statut === 'Livrée' ? 1.0 : min($progression / 100, 1.0);
        $livreur = $this->interpolate(
            self::DEPART['lat'], self::DEPART['lng'],
            $coordsDest['lat'],  $coordsDest['lng'],
            $t
        );

        $fullPrediction = $this->ollama->getFullPrediction(
            $distanceKm, $progression, $statut,
            $coordsDest['lat'], $coordsDest['lng'],
            $adresseDest, $this->extractCity($adresseDest)
        );

        return $this->json([
            'commande_id'       => $id,
            'statut'            => $statut,
            'progression'       => round($progression, 2),
            'depart'            => self::DEPART,
            'destination'       => ['lat' => $coordsDest['lat'], 'lng' => $coordsDest['lng'], 'label' => $adresseDest],
            'livreur'           => ['lat' => $livreur['lat'], 'lng' => $livreur['lng'], 'label' => 'Livreur TunisiaJourney'],
            'distance_km'       => round($distanceKm, 2),
            'distance_restante' => max(0, round($distanceKm * (1 - $progression / 100), 2)),
            'meteo'             => $fullPrediction['meteo'],
            'trafic'            => $fullPrediction['trafic'],
            'ia'                => $fullPrediction['ia'],
            'etapes'            => $this->buildTimeline($statut, $dateC),
            'date_commande'     => $dateC?->format('d/m/Y'),
            'timestamp'         => time(),
        ]);
    }

    private function getProgression(int $id, string $statut): float
    {
        if ($statut === 'Livrée')  return 100.0;
        if ($statut === 'Annulée') return 0.0;
        if ($statut === 'En cours') return $this->realTime($id, 20.0, 100.0);

        return match ($statut) {
            'En attente'     => 0.0,
            'Confirmée'      => 5.0,
            'En préparation' => 15.0,
            'Expédiée'       => $this->realTime($id, 85.0, 100.0),
            default          => 0.0,
        };
    }

    private function realTime(int $id, float $min, float $max): float
    {
        $key   = 'tracking_start_' . $id;
        $start = $this->cache->get($key, function (ItemInterface $item) {
            $item->expiresAfter(86400);
            return time();
        });
        $progress = $min + ((time() - $start) / self::TRAJET_DUREE) * ($max - $min);
        return min($max, max($min, $progress));
    }

    /**
     * @return array<int, array{label: string, icon: string, key: string, done: bool, active: bool, date: string|null}>
     */
    private function buildTimeline(string $statut, ?\DateTimeInterface $dateC): array
    {
        $ordre = ['En attente', 'Confirmée', 'En préparation', 'Expédiée', 'En cours', 'Livrée'];
        $pos   = array_search($statut, $ordre);
        $pos   = $pos !== false ? (int) $pos : 0;

        $rawSteps = [
            ['label' => 'Commande reçue',       'icon' => 'fa-shopping-bag',  'key' => 'En attente'],
            ['label' => 'Confirmée',             'icon' => 'fa-check-circle',  'key' => 'Confirmée'],
            ['label' => 'En préparation',        'icon' => 'fa-box-open',      'key' => 'En préparation'],
            ['label' => 'Expédiée',              'icon' => 'fa-shipping-fast', 'key' => 'Expédiée'],
            ['label' => 'En cours de livraison', 'icon' => 'fa-truck',         'key' => 'En cours'],
            ['label' => 'Livrée ✓',              'icon' => 'fa-home',          'key' => 'Livrée'],
        ];

        $steps = [];
        foreach ($rawSteps as $i => $s) {
            $date = ($i === 0 && $dateC) ? $dateC->format('d/m/Y') : null;
            if ($s['key'] === 'Livrée' && $statut === 'Livrée') {
                $date = date('d/m/Y H:i:s');
            }
            $steps[] = [
                'label'  => $s['label'],
                'icon'   => $s['icon'],
                'key'    => $s['key'],
                'done'   => $i < $pos,
                'active' => $i === $pos,
                'date'   => $date,
            ];
        }

        return $steps;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function geocode(string $adresse): array
    {
        $default = ['lat' => 36.8065, 'lng' => 10.1815];
        return $this->cache->get('geo_' . md5($adresse), function (ItemInterface $item) use ($adresse, $default) {
            $item->expiresAfter(3600);
            $url  = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
                'q' => $adresse . ', Tunisie', 'format' => 'json', 'limit' => 1, 'countrycodes' => 'tn',
            ]);
            $ctx  = stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: TunisiaJourney/1.0\r\n"]]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json) {
                $d = json_decode($json, true);
                if (!empty($d[0])) return ['lat' => (float) $d[0]['lat'], 'lng' => (float) $d[0]['lon']];
            }
            return $default;
        });
    }

    private function haversine(float $la1, float $lo1, float $la2, float $lo2): float
    {
        $R   = 6371;
        $dLa = deg2rad($la2 - $la1);
        $dLo = deg2rad($lo2 - $lo1);
        $a   = sin($dLa / 2) ** 2 + cos(deg2rad($la1)) * cos(deg2rad($la2)) * sin($dLo / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function interpolate(float $la1, float $lo1, float $la2, float $lo2, float $t): array
    {
        return ['lat' => $la1 + ($la2 - $la1) * $t, 'lng' => $lo1 + ($lo2 - $lo1) * $t];
    }

    private function extractCity(string $adresse): string
    {
        $cities = ['Tunis', 'Sfax', 'Sousse', 'Nabeul', 'Bizerte', 'Ariana', 'Ben Arous', 'La Marsa', 'Megrine', 'Manar'];
        foreach ($cities as $city) {
            if (stripos($adresse, $city) !== false) return $city;
        }
        return 'Tunis';
    }
}
