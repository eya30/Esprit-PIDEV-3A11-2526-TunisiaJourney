<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ReservationChApiController extends AbstractController
{
    // 1. Récupérer TOUTES les réservations de chambres (uniquement celles de l'utilisateur connecté)
    #[Route('/reservations/chambres', name: 'api_reservations_chambres', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getReservations(Connection $connection): JsonResponse
    {
        $user = $this->getUser();
        $idUtilisateur = method_exists($user, 'getId') ? $user->getId() : $user->getIdUser();
        
        $sql = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom, h.ville as hotel_ville
                FROM reservation_chambre r 
                JOIN chambre c ON r.idCh = c.idCh 
                JOIN hotel h ON c.idH = h.idH 
                WHERE r.idUtilisateur = ?
                ORDER BY r.id DESC";
        
        $reservations = $connection->fetchAllAssociative($sql, [$idUtilisateur]);
        
        return $this->json($reservations);
    }
    
    // 2. Récupérer UNE réservation de chambre par son ID (vérifier qu'elle appartient à l'utilisateur)
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        $idUtilisateur = method_exists($user, 'getId') ? $user->getId() : $user->getIdUser();
        
        $sql = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom, h.ville as hotel_ville
                FROM reservation_chambre r 
                JOIN chambre c ON r.idCh = c.idCh 
                JOIN hotel h ON c.idH = h.idH 
                WHERE r.id = ? AND r.idUtilisateur = ?";
        
        $reservation = $connection->fetchAssociative($sql, [$id, $idUtilisateur]);
        
        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        return $this->json($reservation);
    }
    
    // 3. Modifier une réservation de chambre (vérifier qu'elle appartient à l'utilisateur)
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateReservation(Request $request, Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        $idUtilisateur = method_exists($user, 'getId') ? $user->getId() : $user->getIdUser();
        
        $data = json_decode($request->getContent(), true);
        
        // Vérifier si la réservation existe et appartient à l'utilisateur
        $checkSql = "SELECT id FROM reservation_chambre WHERE id = ? AND idUtilisateur = ?";
        $exists = $connection->fetchOne($checkSql, [$id, $idUtilisateur]);
        
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        // Mettre à jour la réservation
        $sql = "UPDATE reservation_chambre 
                SET nom = ?, prenom = ?, telephone = ?, email = ?, nbPersonnes = ?
                WHERE id = ? AND idUtilisateur = ?";
        
        $connection->executeStatement($sql, [
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['telephone'] ?? '',
            $data['email'] ?? '',
            $data['nbre'] ?? 1,
            $id,
            $idUtilisateur
        ]);
        
        return $this->json(['success' => true]);
    }
    
    // 4. Supprimer une réservation de chambre (vérifier qu'elle appartient à l'utilisateur)
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        $idUtilisateur = method_exists($user, 'getId') ? $user->getId() : $user->getIdUser();
        
        // Vérifier si la réservation existe et appartient à l'utilisateur
        $checkSql = "SELECT id FROM reservation_chambre WHERE id = ? AND idUtilisateur = ?";
        $exists = $connection->fetchOne($checkSql, [$id, $idUtilisateur]);
        
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        // Supprimer la réservation
        $connection->executeStatement("DELETE FROM reservation_chambre WHERE id = ? AND idUtilisateur = ?", [$id, $idUtilisateur]);
        
        return $this->json(['success' => true]);
    }
}