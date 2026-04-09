<?php

namespace App\Controller\Api;

use App\Entity\User;
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
        $user = $this->getUser();
        
        // Si pas d'utilisateur connecté, retourner tableau vide
        if (!$user) {
            return $this->json([]);
        }
        
        // Récupérer l'email de l'utilisateur connecté
        $email = $user->getUserIdentifier();
        
        // 🔥 Récupérer les réservations par EMAIL au lieu de user_id
        $reservations = $connection->fetchAllAssociative(
            "SELECT * FROM reservationprog WHERE email = :email ORDER BY idRP DESC",
            ['email' => $email]
        );
        
        return $this->json($reservations);
    }
    
    #[Route('/reservations/{id}', name: 'api_reservation_get', methods: ['GET'])]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }
        
        $email = $user->getUserIdentifier();
        
        $reservation = $connection->fetchAssociative(
            "SELECT * FROM reservationprog WHERE idRP = :id AND email = :email",
            ['id' => $id, 'email' => $email]
        );
        
        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
        return $this->json($reservation);
    }
    
    #[Route('/reservations/{id}', name: 'api_reservation_update', methods: ['PUT'])]
    public function updateReservation(Request $request, Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }
        
        $email = $user->getUserIdentifier();
        $data = json_decode($request->getContent(), true);
        
        // Vérifier que la réservation appartient à l'utilisateur
        $existing = $connection->fetchAssociative(
            "SELECT idRP FROM reservationprog WHERE idRP = :id AND email = :email",
            ['id' => $id, 'email' => $email]
        );
        
        if (!$existing) {
            return $this->json(['error' => 'Réservation non trouvée ou non autorisée'], 404);
        }
        
        $connection->executeStatement(
            "UPDATE reservationprog SET nom = :nom, prenom = :prenom, telephone = :telephone, email = :email, nbre = :nbre WHERE idRP = :id",
            [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'telephone' => $data['telephone'],
                'email' => $data['email'],
                'nbre' => $data['nbre'],
                'id' => $id
            ]
        );
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/reservations/{id}', name: 'api_reservation_delete', methods: ['DELETE'])]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }
        
        $email = $user->getUserIdentifier();
        
        // Vérifier que la réservation appartient à l'utilisateur
        $existing = $connection->fetchAssociative(
            "SELECT idRP FROM reservationprog WHERE idRP = :id AND email = :email",
            ['id' => $id, 'email' => $email]
        );
        
        if (!$existing) {
            return $this->json(['error' => 'Réservation non trouvée ou non autorisée'], 404);
        }
        
        $connection->executeStatement(
            "DELETE FROM reservationprog WHERE idRP = :id",
            ['id' => $id]
        );
        
        return $this->json(['success' => true]);
    }
}