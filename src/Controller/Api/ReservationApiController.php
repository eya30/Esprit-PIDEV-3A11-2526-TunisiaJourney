<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ReservationApiController extends AbstractController
{
    #[Route('/reservations', name: 'api_reservations', methods: ['GET'])]
    public function getReservations(Connection $connection): JsonResponse
    {
        $reservations = $connection->fetchAllAssociative("
            SELECT * FROM reservationprog ORDER BY idRP DESC
        ");
        
        return $this->json($reservations);
    }
    
    #[Route('/reservations/{id}', name: 'api_reservation_get', methods: ['GET'])]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
        $reservation = $connection->fetchAssociative("SELECT * FROM reservationprog WHERE idRP = ?", [$id]);
        
        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        return $this->json($reservation);
    }
    
    #[Route('/reservations/{id}', name: 'api_reservation_update', methods: ['PUT'])]
    public function updateReservation(Request $request, Connection $connection, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $connection->executeStatement(
            "UPDATE reservationprog SET nom = ?, prenom = ?, telephone = ?, email = ?, nbre = ? WHERE idRP = ?",
            [$data['nom'], $data['prenom'], $data['telephone'], $data['email'], $data['nbre'], $id]
        );
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/reservations/{id}', name: 'api_reservation_delete', methods: ['DELETE'])]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
        $connection->executeStatement("DELETE FROM reservationprog WHERE idRP = ?", [$id]);
        
        return $this->json(['success' => true]);
    }
}