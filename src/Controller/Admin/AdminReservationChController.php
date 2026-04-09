<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;

#[Route('/admin/reservation')]
class AdminReservationChController extends AbstractController
{
    const ITEMS_PER_PAGE = 10;
    
    #[Route('/chambre/{idCh}', name: 'admin_reservationch_index')]
    public function index(Connection $connection, Request $request, int $idCh): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'idRes');
        $direction = $request->query->get('direction', 'DESC');
        
        // Récupérer les informations de la chambre
        $chambre = $connection->fetchAssociative("
            SELECT c.*, h.nom as hotel_nom, h.ville as hotel_ville 
            FROM chambre c 
            LEFT JOIN hotel h ON c.idH = h.idH 
            WHERE c.idCh = ?
        ", [$idCh]);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre non trouvée');
        }
        
        // Colonnes autorisées pour le tri
        $allowedSorts = ['idRes', 'idUtilisateur', 'telephone', 'nbNuit', 'prixTotal', 'nbPersonnes', 'statut'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'idRes';
        }
        
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        // Construction de la requête avec recherche
        $searchCondition = "";
        $params = ['idCh' => $idCh];
        
        if (!empty($search)) {
            $searchCondition = " AND (r.idUtilisateur LIKE :search OR r.telephone LIKE :search OR r.statut LIKE :search) ";
            $params['search'] = "%$search%";
        }
        
        // Compter le nombre total de réservations
        $countQuery = "
            SELECT COUNT(*) FROM reservation_chambre r 
            WHERE r.idCh = :idCh
            $searchCondition
        ";
        $totalReservations = $connection->fetchOne($countQuery, $params);
        $totalPages = max(1, ceil($totalReservations / self::ITEMS_PER_PAGE));
        
        // Récupérer les réservations paginées
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        $reservations = $connection->fetchAllAssociative("
            SELECT r.*
            FROM reservation_chambre r 
            WHERE r.idCh = :idCh
            $searchCondition
            ORDER BY r.$sort $direction
            LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)$offset,
            $params
        );
        
        return $this->render('admin/admin_reservation_ch/index.html.twig', [
            'chambre' => $chambre,
            'reservations' => $reservations,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'total_count' => $totalReservations,
        ]);
    }

    #[Route('/chambre/{idCh}/pdf', name: 'admin_reservationch_pdf')]
    public function pdf(Connection $connection, int $idCh): Response
    {
        $chambre = $connection->fetchAssociative("
            SELECT c.*, h.nom as hotel_nom, h.ville as hotel_ville, h.adresse as hotel_adresse
            FROM chambre c 
            LEFT JOIN hotel h ON c.idH = h.idH 
            WHERE c.idCh = ?
        ", [$idCh]);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre non trouvée');
        }
        
        $reservations = $connection->fetchAllAssociative("
            SELECT * FROM reservation_chambre 
            WHERE idCh = ? 
            ORDER BY dateDebut DESC
        ", [$idCh]);
        
        $html = $this->renderView('admin/admin_reservation_ch/pdf.html.twig', [
            'chambre' => $chambre,
            'reservations' => $reservations,
            'date_generation' => date('d/m/Y H:i:s'),
        ]);
        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reservations_chambre_' . $chambre['num'] . '_' . $chambre['hotel_nom'] . '.pdf"'
            ]
        );
    }

    // MÉTHODE DELETE AJOUTÉE
    #[Route('/{id}/delete', name: 'admin_reservationch_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete_reservationch_' . $id, $request->request->get('_token'))) {
            $connection->executeStatement("DELETE FROM reservation_chambre WHERE idRes = ?", [$id]);
            $this->addFlash('success', 'Réservation supprimée avec succès !');
        }
        
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        return $this->redirectToRoute('admin_chambre_index');
    }
}