<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    // ── Helpers partagés ────────────────────────────────────────────────────

    private function buildQuery(string $search, string $sort): array
    {
        $where  = '';
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where = "WHERE (
                          Titre        LIKE ?
                       OR Description  LIKE ?
                       OR Lieu         LIKE ?
                       OR Organisateur LIKE ?
                       OR DATE_FORMAT(DateDebut, '%d/%m/%Y') LIKE ?
                       OR DATE_FORMAT(DateFin,   '%d/%m/%Y') LIKE ?
                       OR CAST(CapaciteMax AS CHAR) LIKE ?
                    )";
            $params = [$like, $like, $like, $like, $like, $like, $like];
        }

        $orderBy = match ($sort) {
            'capacity_asc'  => 'ORDER BY CapaciteMax ASC',
            'name_asc'      => 'ORDER BY Titre ASC',
            'organizer_asc' => 'ORDER BY Organisateur ASC',
            default         => 'ORDER BY DateDebut ASC',
        };

        return [$where, $orderBy, $params];
    }

    private function enrichEvenements(array $evenements, Connection $connection): array
    {
        if (empty($evenements)) {
            return $evenements;
        }

        $ids = array_column($evenements, 'IDEv');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $counts = $connection->fetchAllAssociative(
            "SELECT a.IDEv, COUNT(r.IDAct) as total
             FROM Activite a
             LEFT JOIN reservationact r ON r.IDAct = a.IDAct
             WHERE a.IDEv IN ($placeholders)
             GROUP BY a.IDEv",
            $ids
        );

        $countMap = [];
        foreach ($counts as $row) {
            $countMap[$row['IDEv']] = (int) $row['total'];
        }

        foreach ($evenements as &$event) {
            if (!isset($event['Prix'])) {
                $event['Prix'] = rand(50, 500);
            }
            if (!isset($event['Categorie'])) {
                $categories = ['festival', 'sport', 'culture', 'gastronomie', 'musique', 'art'];
                $event['Categorie'] = $categories[array_rand($categories)];
            }
            $event['ReservationsCount'] = $countMap[$event['IDEv']] ?? 0;
        }
        unset($event);

        return $evenements;
    }

    // ── Helper : calcul places restantes pour les activités d'un événement ──

    private function buildPlacesData(array $activites, Connection $connection): array
    {
        $placesData    = [];
        $typesActivite = [];

        if (empty($activites)) {
            return [$placesData, $typesActivite];
        }

        $ids          = array_column($activites, 'IDAct');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Une seule requête pour toutes les réservations
        $reservations = $connection->fetchAllAssociative(
            "SELECT IDAct, COALESCE(SUM(NombrePlaces), 0) AS totalReserve
             FROM reservationact
             WHERE IDAct IN ($placeholders)
             GROUP BY IDAct",
            $ids
        );

        $reserveMap = [];
        foreach ($reservations as $row) {
            $reserveMap[(int)$row['IDAct']] = (int)$row['totalReserve'];
        }

        foreach ($activites as $activite) {
            $id          = (int)$activite['IDAct'];
            $capacite    = (int)$activite['CapaciteM'];
            $reserve     = $reserveMap[$id] ?? 0;
            $restantes   = max(0, $capacite - $reserve);
            $pourcentage = $capacite > 0 ? round(($reserve / $capacite) * 100) : 100;

            if ($pourcentage >= 100) {
                $disponibilite = 'soldout';
            } elseif ($pourcentage >= 80) {
                $disponibilite = 'warning';
            } else {
                $disponibilite = 'available';
            }

            $placesData[$id] = [
                'placesRestantes'        => $restantes,
                'placesReservees'        => $reserve,
                'pourcentageRemplissage' => $pourcentage,
                'disponibilite'          => $disponibilite,
            ];

            // Collecter les types uniques pour le filtre
            $type = $activite['TypeActivite'] ?? null;
            if ($type && !in_array($type, $typesActivite)) {
                $typesActivite[] = $type;
            }
        }

        sort($typesActivite);

        return [$placesData, $typesActivite];
    }

    // ── Page principale ──────────────────────────────────────────────────────

    #[Route('/', name: 'app_evenement_index')]
    public function index(Connection $connection): Response
    {
        $evenements = $connection->fetchAllAssociative(
            "SELECT * FROM Evenement ORDER BY DateDebut ASC"
        );
        $evenements = $this->enrichEvenements($evenements, $connection);

        return $this->render('evenement/indexev.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    // ── Route AJAX : recherche + tri ─────────────────────────────────────────

    #[Route('/search', name: 'app_evenement_search', methods: ['GET'])]
    public function search(Connection $connection, Request $request): JsonResponse
    {
        $search = trim($request->query->get('search', ''));
        $sort   = $request->query->get('sort', 'date_asc');

        [$where, $orderBy, $params] = $this->buildQuery($search, $sort);

        $evenements = $connection->fetchAllAssociative(
            "SELECT * FROM Evenement $where $orderBy",
            $params
        );
        $evenements = $this->enrichEvenements($evenements, $connection);

        $cards = [];
        foreach ($evenements as $ev) {
            $placesRestantes = $ev['CapaciteMax'] - ($ev['ReservationsCount'] ?? 0);
            $imageSrc = $ev['Image']
                ? '/uploads/evenements/' . basename($ev['Image'])
                : 'https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg?auto=compress&cs=tinysrgb&w=800';

            $dateDebut    = (new \DateTime($ev['DateDebut']))->format('d/m/Y');
            $dateFin      = (new \DateTime($ev['DateFin']))->format('d/m/Y');
            $organisateur = htmlspecialchars(mb_substr($ev['Organisateur'] ?? '', 0, 22));
            $titre        = htmlspecialchars($ev['Titre']);
            $lieu         = htmlspecialchars($ev['Lieu']);
            $desc         = htmlspecialchars(mb_substr($ev['Description'] ?? '', 0, 200));
            $idEv         = (int) $ev['IDEv'];

            $cards[] = [
                'id'              => $idEv,
                'titre'           => $titre,
                'lieu'            => $lieu,
                'description'     => $desc,
                'dateDebut'       => $dateDebut,
                'dateFin'         => $dateFin,
                'dateDebutRaw'    => $ev['DateDebut'],
                'dateFinRaw'      => $ev['DateFin'],
                'capacite'        => (int) $ev['CapaciteMax'],
                'placesRestantes' => $placesRestantes,
                'organisateur'    => $organisateur,
                'image'           => $imageSrc,
                'urlDetails'      => $this->generateUrl('app_evenement_show', ['IDEv' => $idEv]),
                'urlActivites'    => $this->generateUrl('app_evenement_activites', ['IDEv' => $idEv]),
            ];
        }

        return new JsonResponse([
            'count' => count($cards),
            'cards' => $cards,
        ]);
    }

    // ── Détail d'un événement ────────────────────────────────────────────────

    #[Route('/{IDEv}', name: 'app_evenement_show')]
    public function show(Connection $connection, int $IDEv): Response
    {
        $evenement = $connection->fetchAssociative(
            "SELECT * FROM Evenement WHERE IDEv = ?", [$IDEv]
        );

        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        $evenement['ReservationsCount'] = (int) $connection->fetchOne(
            "SELECT COUNT(r.IDAct)
             FROM Activite a
             LEFT JOIN reservationact r ON r.IDAct = a.IDAct
             WHERE a.IDEv = ?",
            [$IDEv]
        );

        $activites = $connection->fetchAllAssociative(
            "SELECT * FROM Activite WHERE IDEv = ? ORDER BY HeureDebut ASC", [$IDEv]
        );

        return $this->render('evenement/showev.html.twig', [
            'evenement' => $evenement,
            'activites' => $activites,
        ]);
    }

    // ── Liste des activités d'un événement ───────────────────────────────────

    #[Route('/{IDEv}/activites', name: 'app_evenement_activites')]
    public function activites(Connection $connection, int $IDEv): Response
    {
        $evenement = $connection->fetchAssociative(
            "SELECT * FROM Evenement WHERE IDEv = ?", [$IDEv]
        );

        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        $activites = $connection->fetchAllAssociative(
            "SELECT * FROM Activite WHERE IDEv = ? ORDER BY HeureDebut ASC", [$IDEv]
        );

        // Calcul des places restantes + types uniques pour les filtres
        [$placesData, $typesActivite] = $this->buildPlacesData($activites, $connection);

        return $this->render('evenement/activites_ev.html.twig', [
            'evenement'     => $evenement,
            'activites'     => $activites,
            'placesData'    => $placesData,
            'typesActivite' => $typesActivite,
        ]);
    }
}