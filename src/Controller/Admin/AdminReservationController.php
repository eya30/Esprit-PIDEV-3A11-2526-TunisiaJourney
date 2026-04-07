<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/reservations')]
class AdminReservationController extends AbstractController
{
    const ITEMS_PER_PAGE = 10;
    
    #[Route('/', name: 'admin_reservation_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'idRP');
        $direction = $request->query->get('direction', 'DESC');
        
        $allowedSorts = ['idRP', 'nom', 'prenom', 'email', 'telephone', 'nbre', 'prixProg', 'dateProgramme', 'statutPaiement', 'programme_nom'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'idRP';
        }
        
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $searchCondition = "";
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = " WHERE (r.nom LIKE :search OR r.prenom LIKE :search OR r.email LIKE :search OR r.telephone LIKE :search OR p.nom LIKE :search) ";
            $params['search'] = "%$search%";
        }
        
        $countQuery = "
            SELECT COUNT(*) FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            $searchCondition
        ";
        $totalReservations = $connection->fetchOne($countQuery, $params);
        $totalPages = max(1, ceil($totalReservations / self::ITEMS_PER_PAGE));
        
        $orderByClause = "";
        if ($sort === 'programme_nom') {
            $orderByClause = " ORDER BY p.nom $direction";
        } else {
            $orderByClause = " ORDER BY r.$sort $direction";
        }
        
        $reservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom, v.nom as voyage_nom
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            LEFT JOIN voyages v ON p.idV = v.idV
            $searchCondition
            $orderByClause
            LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)(($page - 1) * self::ITEMS_PER_PAGE),
            $params
        );
        
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'total_count' => $totalReservations,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete_reservation_' . $id, $request->request->get('_token'))) {
            $connection->executeStatement("DELETE FROM reservationprog WHERE idRP = ?", [$id]);
            $this->addFlash('success', 'Réservation supprimée avec succès !');
        }
        
        return $this->redirectToRoute('admin_reservation_index');
    }

    #[Route('/pdf', name: 'admin_reservation_pdf')]
    public function pdf(Connection $connection): Response
    {
        $reservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom, v.nom as voyage_nom
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            LEFT JOIN voyages v ON p.idV = v.idV
            ORDER BY r.dateProgramme DESC
        ");

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->renderView('admin/reservation/pdf.html.twig', [
            'reservations' => $reservations,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport_reservations_' . date('Y-m-d') . '.pdf"'
        ]);
    }
}