<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ReservationChApiController extends AbstractController
{
    // 1. Récupérer TOUTES les réservations de chambres
    #[Route('/reservations/chambres', name: 'api_reservations_chambres', methods: ['GET'])]
    public function getReservations(Connection $connection): JsonResponse
    {
        $sql = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom 
                FROM reservation_chambre r 
                JOIN chambre c ON r.idCh = c.idCh 
                JOIN hotel h ON c.idH = h.idH 
                ORDER BY r.id DESC";
        
        $reservations = $connection->fetchAllAssociative($sql);
        
        return $this->json($reservations);
    }
    
    // 2. Récupérer UNE réservation de chambre par son ID
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_get', methods: ['GET'])]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
        $sql = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom 
                FROM reservation_chambre r 
                JOIN chambre c ON r.idCh = c.idCh 
                JOIN hotel h ON c.idH = h.idH 
                WHERE r.id = ?";
        
        $reservation = $connection->fetchAssociative($sql, [$id]);
        
        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        return $this->json($reservation);
    }
    
    // 3. Modifier une réservation de chambre
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_update', methods: ['PUT'])]
    public function updateReservation(Request $request, Connection $connection, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Vérifier si la réservation existe
        $checkSql = "SELECT id FROM reservation_chambre WHERE id = ?";
        $exists = $connection->fetchOne($checkSql, [$id]);
        
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        // Mettre à jour la réservation
        $sql = "UPDATE reservation_chambre 
                SET nom = ?, prenom = ?, telephone = ?, email = ?, nbPersonnes = ?
                WHERE id = ?";
        
        $connection->executeStatement($sql, [
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['telephone'] ?? '',
            $data['email'] ?? '',
            $data['nbre'] ?? 1,
            $id
        ]);
        
        return $this->json(['success' => true]);
    }
    
    // 4. Supprimer une réservation de chambre
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_delete', methods: ['DELETE'])]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
        // Vérifier si la réservation existe
        $checkSql = "SELECT id FROM reservation_chambre WHERE id = ?";
        $exists = $connection->fetchOne($checkSql, [$id]);
        
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        // Supprimer la réservation
        $connection->executeStatement("DELETE FROM reservation_chambre WHERE id = ?", [$id]);
        
        return $this->json(['success' => true]);
    }
}