<?php

namespace App\Controller;

use App\Repository\CodePromoRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evenement')]
class EvenementController extends AbstractController
{
<<<<<<< HEAD
    /**
     * @return array{0: string, 1: string, 2: list<string>}
     */
=======
    // ── Helpers partagés ────────────────────────────────────────────────────

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    /**
     * @param array<int, array<string, mixed>> $evenements
     * @return array<int, array<string, mixed>>
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    /**
     * @param array<int, array<string, mixed>> $activites
     * @return array{0: array<int, array<string, mixed>>, 1: list<string>}
     */
=======
    // ── Helper : calcul places restantes pour les activités d'un événement ──

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function buildPlacesData(array $activites, Connection $connection): array
    {
        $placesData    = [];
        $typesActivite = [];

        if (empty($activites)) {
            return [$placesData, $typesActivite];
        }

        $ids          = array_column($activites, 'IDAct');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $reservations = $connection->fetchAllAssociative(
            "SELECT IDAct, COALESCE(SUM(NombrePlaces), 0) AS totalReserve
             FROM reservationact
<<<<<<< HEAD
             WHERE IDAct IN ($placeholders) AND status = 'confirmé'
=======
             WHERE IDAct IN ($placeholders)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
             GROUP BY IDAct",
            $ids
        );

        $reserveMap = [];
        foreach ($reservations as $row) {
            $reserveMap[(int)$row['IDAct']] = (int)$row['totalReserve'];
        }

        foreach ($activites as $activite) {
<<<<<<< HEAD
            $id       = (int)$activite['IDAct'];
            $capacite = (int)$activite['CapaciteM'];
            $reserve  = $reserveMap[$id] ?? 0;

            if ($capacite <= 0) {
                $placesData[$id] = [
                    'placesRestantes'        => 0,
                    'placesReservees'        => 0,
                    'pourcentageRemplissage' => 0,
                    'disponibilite'          => 'available',
                ];
                $type = isset($activite['TypeActivite']) ? (string)$activite['TypeActivite'] : null;
                if ($type !== null && $type !== '' && !in_array($type, $typesActivite, true)) {
                    $typesActivite[] = $type;
                }
                continue;
            }

            $restantes   = max(0, $capacite - $reserve);
            $pourcentage = round(($reserve / $capacite) * 100);

            if ($pourcentage >= 100)    $disponibilite = 'soldout';
            elseif ($pourcentage >= 80) $disponibilite = 'warning';
            else                        $disponibilite = 'available';
=======
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
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

            $placesData[$id] = [
                'placesRestantes'        => $restantes,
                'placesReservees'        => $reserve,
                'pourcentageRemplissage' => $pourcentage,
                'disponibilite'          => $disponibilite,
            ];

<<<<<<< HEAD
            $type = isset($activite['TypeActivite']) ? (string)$activite['TypeActivite'] : null;
            if ($type !== null && $type !== '' && !in_array($type, $typesActivite, true)) {
=======
            $type = $activite['TypeActivite'] ?? null;
            if ($type && !in_array($type, $typesActivite)) {
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                $typesActivite[] = $type;
            }
        }

        sort($typesActivite);

        return [$placesData, $typesActivite];
    }

<<<<<<< HEAD
=======
    // ── Page principale ──────────────────────────────────────────────────────

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Route('/', name: 'app_evenement_index')]
    public function index(Connection $connection, CodePromoRepository $codePromoRepository): Response
    {
        $evenements = $connection->fetchAllAssociative(
            "SELECT * FROM Evenement ORDER BY DateDebut ASC"
        );
        $evenements = $this->enrichEvenements($evenements, $connection);

<<<<<<< HEAD
        $codesValides   = $codePromoRepository->findAllValides();
=======
        // ── Récupérer le premier code promo actif valide aujourd'hui ──
        $codesValides = $codePromoRepository->findAllValides();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $codePromoActif = !empty($codesValides) ? $codesValides[0] : null;

        return $this->render('evenement/indexev.html.twig', [
            'evenements'     => $evenements,
            'codePromoActif' => $codePromoActif,
        ]);
    }

<<<<<<< HEAD
    #[Route('/search', name: 'app_evenement_search', methods: ['GET'])]
    public function search(Connection $connection, Request $request): JsonResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort   = (string) $request->query->get('sort', 'date_asc');
=======
    // ── Route AJAX : recherche + tri ─────────────────────────────────────────

    #[Route('/search', name: 'app_evenement_search', methods: ['GET'])]
    public function search(Connection $connection, Request $request): JsonResponse
    {
        $search = trim($request->query->get('search', ''));
        $sort   = $request->query->get('sort', 'date_asc');
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

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
<<<<<<< HEAD
                ? '/uploads/evenements/' . basename((string) $ev['Image'])
                : 'https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg?auto=compress&cs=tinysrgb&w=800';

            $dateDebut    = (new \DateTime((string) $ev['DateDebut']))->format('d/m/Y');
            $dateFin      = (new \DateTime((string) $ev['DateFin']))->format('d/m/Y');
            $organisateur = htmlspecialchars(mb_substr((string) ($ev['Organisateur'] ?? ''), 0, 22));
            $titre        = htmlspecialchars((string) $ev['Titre']);
            $lieu         = htmlspecialchars((string) $ev['Lieu']);
            $desc         = htmlspecialchars(mb_substr((string) ($ev['Description'] ?? ''), 0, 200));
=======
                ? '/uploads/evenements/' . basename($ev['Image'])
                : 'https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg?auto=compress&cs=tinysrgb&w=800';

            $dateDebut    = (new \DateTime($ev['DateDebut']))->format('d/m/Y');
            $dateFin      = (new \DateTime($ev['DateFin']))->format('d/m/Y');
            $organisateur = htmlspecialchars(mb_substr($ev['Organisateur'] ?? '', 0, 22));
            $titre        = htmlspecialchars($ev['Titre']);
            $lieu         = htmlspecialchars($ev['Lieu']);
            $desc         = htmlspecialchars(mb_substr($ev['Description'] ?? '', 0, 200));
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    #[Route('/{IDEv}', name: 'app_evenement_show')]
    public function show(Connection $connection, int $IDEv): Response
    {
        $evenement = $connection->fetchAssociative(
            "SELECT *, Latitude, Longitude FROM Evenement WHERE IDEv = ?",
=======
    // ── Détail d'un événement (MODIFIÉ) ─────────────────────────────────────

    #[Route('/{IDEv}', name: 'app_evenement_show')]
    public function show(Connection $connection, int $IDEv): Response
    {
        // 🔴 MODIFICATION : Ajout de Latitude et Longitude dans la requête
        $evenement = $connection->fetchAssociative(
            "SELECT *, Latitude, Longitude FROM Evenement WHERE IDEv = ?", 
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            [$IDEv]
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
<<<<<<< HEAD
            "SELECT * FROM Activite WHERE IDEv = ? ORDER BY HeureDebut ASC",
=======
            "SELECT * FROM Activite WHERE IDEv = ? ORDER BY HeureDebut ASC", 
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            [$IDEv]
        );

        return $this->render('evenement/showev.html.twig', [
            'evenement' => $evenement,
            'activites' => $activites,
        ]);
    }

<<<<<<< HEAD
=======
    // ── Liste des activités d'un événement ───────────────────────────────────

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

        [$placesData, $typesActivite] = $this->buildPlacesData($activites, $connection);

        return $this->render('evenement/activites_ev.html.twig', [
            'evenement'     => $evenement,
            'activites'     => $activites,
            'placesData'    => $placesData,
            'typesActivite' => $typesActivite,
        ]);
    }
}