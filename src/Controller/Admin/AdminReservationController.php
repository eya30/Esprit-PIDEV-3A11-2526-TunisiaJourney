<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservations')]
class AdminReservationController extends AbstractController
{
    const ITEMS_PER_PAGE = 10;
    
    #[Route('/', name: 'admin_reservation_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        
        // Compter le nombre total de réservations
        $totalReservations = $connection->fetchOne("SELECT COUNT(*) FROM reservationprog");
        $totalPages = max(1, ceil($totalReservations / self::ITEMS_PER_PAGE));
        
        // Récupérer les réservations paginées
        $reservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom, v.nom as voyage_nom
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            LEFT JOIN voyages v ON p.idV = v.idV
            ORDER BY r.idRP DESC
            LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)$offset
        );
        
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'current_page' => $page,
            'total_pages' => $totalPages,
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
}