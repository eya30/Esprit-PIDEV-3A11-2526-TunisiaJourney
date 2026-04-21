<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservations-act')]
class AdminReservationActController extends AbstractController
{
    const ITEMS_PER_PAGE = 1; // ⚠️ Changé temporairement à 2 pour voir la pagination !!!

    #[Route('/{id}', name: 'admin_activite_reservations')]
    public function index(Connection $connection, Request $request, int $id): Response
    {
        // Récupérer l'activité
        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$id]
        );
        
        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }
        
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'date_desc');
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        // Construction de la requête WHERE pour la recherche
        $whereClause = " WHERE r.IDAct = :id";
        $params = ['id' => $id];
        
        if (!empty($search)) {
            $whereClause .= " AND (r.Nom LIKE :search 
                           OR r.Prenom LIKE :search 
                           OR r.email LIKE :search 
                           OR r.telephone LIKE :search 
                           OR r.NombrePlaces LIKE :search 
                           OR r.Prix LIKE :search 
                           OR r.DateReservation LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        // Construction de la clause ORDER BY pour le tri
        $orderBy = match($sort) {
            'client_asc' => "ORDER BY r.Prenom ASC, r.Nom ASC",
            'date_asc' => "ORDER BY r.DateReservation ASC",
            'places_asc' => "ORDER BY r.NombrePlaces ASC",
            'prix_asc' => "ORDER BY r.Prix ASC",
            default => "ORDER BY r.DateReservation DESC"
        };

        // Comptage total
        $totalSql = "SELECT COUNT(*) FROM ReservationAct r $whereClause";
        $total = $connection->fetchOne($totalSql, $params);
        $totalPages = max(1, ceil($total / self::ITEMS_PER_PAGE));

        // Ajuster la page si elle dépasse le nombre total de pages
        if ($page > $totalPages && $totalPages > 0) {
            return $this->redirectToRoute('admin_activite_reservations', [
                'id' => $id,
                'page' => $totalPages,
                'search' => $search,
                'sort' => $sort,
            ]);
        }

        // Récupération des réservations
        $sql = "
            SELECT r.* 
            FROM ReservationAct r 
            $whereClause
            $orderBy
            LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)$offset;
        
        $reservations = $connection->fetchAllAssociative($sql, $params);

        // 🔍 DEBUG : Vérifier les valeurs dans les logs Symfony
        error_log('=== DÉBOGAGE PAGINATION RÉSERVATIONS ===');
        error_log('Total réservations: ' . $total);
        error_log('Items par page: ' . self::ITEMS_PER_PAGE);
        error_log('Total pages: ' . $totalPages);
        error_log('Page actuelle: ' . $page);
        error_log('=========================================');

        return $this->render('admin/activite/reservationsact.html.twig', [
            'reservations' => $reservations,
            'activite' => $activite,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/{id}/export-pdf', name: 'admin_reservations_export_pdf')]
    public function exportPdf(Connection $connection, Request $request, int $id): Response
    {
        // Récupérer l'activité
        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$id]
        );
        
        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }
        
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'date_desc');
        
        // Construction de la requête WHERE pour la recherche
        $whereClause = " WHERE r.IDAct = :id";
        $params = ['id' => $id];
        
        if (!empty($search)) {
            $whereClause .= " AND (r.Nom LIKE :search 
                           OR r.Prenom LIKE :search 
                           OR r.email LIKE :search 
                           OR r.telephone LIKE :search 
                           OR r.NombrePlaces LIKE :search 
                           OR r.Prix LIKE :search 
                           OR r.DateReservation LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        // Construction de la clause ORDER BY pour le tri
        $orderBy = match($sort) {
            'client_asc' => "ORDER BY r.Prenom ASC, r.Nom ASC",
            'date_asc' => "ORDER BY r.DateReservation ASC",
            'places_asc' => "ORDER BY r.NombrePlaces ASC",
            'prix_asc' => "ORDER BY r.Prix ASC",
            default => "ORDER BY r.DateReservation DESC"
        };
        
        // Récupération des réservations (sans pagination pour le PDF)
        $sql = "SELECT r.* FROM ReservationAct r $whereClause $orderBy";
        $reservations = $connection->fetchAllAssociative($sql, $params);
        
        $now = new \DateTime();
        
        // Rendu du template HTML dédié au PDF
        $html = $this->renderView('admin/activite/reservationsAct_export_pdf.html.twig', [
            'reservations' => $reservations,
            'activite' => $activite,
            'generated_at' => $now,
            'search' => $search,
            'sort' => $sort,
            'total' => count($reservations)
        ]);
        
        // Configuration Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        // Nettoyer le nom du fichier (enlever les caractères spéciaux)
        $cleanTitle = preg_replace('/[^a-zA-Z0-9]/', '_', $activite['Titre']);
        $filename = 'reservations_' . $cleanTitle . '_' . $now->format('Y-m-d') . '.pdf';
        
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/{id}/delete', name: 'admin_reservationact_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        // Récupérer l'IDAct avant suppression pour la redirection
        $reservation = $connection->fetchAssociative(
            "SELECT IDAct FROM ReservationAct WHERE IDRes = ?",
            [$id]
        );
        
        if ($reservation && $this->isCsrfTokenValid('delete_reservationact_' . $id, $request->request->get('_token'))) {
            $connection->executeStatement("DELETE FROM ReservationAct WHERE IDRes = ?", [$id]);
            $this->addFlash('success', 'Réservation supprimée avec succès !');
            return $this->redirectToRoute('admin_activite_reservations', ['id' => $reservation['IDAct']]);
        }

        $this->addFlash('error', 'Erreur lors de la suppression de la réservation.');
        return $this->redirectToRoute('admin_activite_reservations', ['id' => $reservation['IDAct'] ?? 0]);
    }
}