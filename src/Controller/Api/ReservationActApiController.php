<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ReservationActApiController extends AbstractController
{
    #[Route('/reservations-act', name: 'api_reservations_act', methods: ['GET'])]
    public function getReservations(Connection $connection): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([]);
        }

        $email = $user->getUserIdentifier();

        $reservations = $connection->fetchAllAssociative(
            "SELECT * FROM ReservationAct WHERE email = :email ORDER BY IDRes DESC",
            ['email' => $email]
        );

        return $this->json($reservations);
    }

    #[Route('/reservations/activite/{id}', name: 'api_reservations_by_activite', methods: ['GET'])]
    public function getReservationsByActivite(Connection $connection, int $id): JsonResponse
    {
        // Retourne uniquement les réservations du user connecté pour cette activité (IDAct)
        $user = $this->getUser();
        if (!$user) {
            return $this->json([]);
        }

        $email = $user->getUserIdentifier();

        $reservations = $connection->fetchAllAssociative(
            "SELECT * FROM reservationact WHERE IDAct = :id AND email = :email ORDER BY IDRes DESC",
            ['id' => $id, 'email' => $email]
        );

        return $this->json($reservations);
    }

    #[Route('/reservations/evenement/{idEv}', name: 'api_reservations_by_evenement', methods: ['GET'])]
    public function getReservationsByEvenement(Connection $connection, int $idEv): JsonResponse
    {
        // Retourne uniquement les réservations du user connecté pour les activités rattachées à l'événement (IDEv)
        $user = $this->getUser();
        if (!$user) {
            return $this->json([]);
        }

        $email = $user->getUserIdentifier();

        $reservations = $connection->fetchAllAssociative(
            "SELECT r.* FROM reservationact r
             JOIN activite a ON r.IDAct = a.IDAct
             WHERE a.IDEv = :idEv AND r.email = :email
             ORDER BY r.IDRes DESC",
            ['idEv' => $idEv, 'email' => $email]
        );

        return $this->json($reservations);
    }

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_get', methods: ['GET'])]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $email = $user->getUserIdentifier();

        $reservation = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = :id AND email = :email",
            ['id' => $id, 'email' => $email]
        );

        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }

        return $this->json($reservation);
    }

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_update', methods: ['PUT'])]
    public function updateReservation(Request $request, Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $email = $user->getUserIdentifier();
        $data = json_decode($request->getContent(), true);

        $existing = $connection->fetchAssociative(
            "SELECT IDRes FROM ReservationAct WHERE IDRes = :id AND email = :email",
            ['id' => $id, 'email' => $email]
        );

        if (!$existing) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        // Accepter les deux formats (minuscule depuis JS, majuscule si autre source)
        $nom          = $data['nom']          ?? $data['Nom']          ?? '';
        $prenom       = $data['prenom']       ?? $data['Prenom']       ?? '';
        $nombrePlaces = $data['nbre']         ?? $data['NombrePlaces'] ?? 1;
        $telephone    = $data['telephone']    ?? '';
        $emailUpdate  = $data['email']        ?? $email;

        $connection->executeStatement(
            "UPDATE ReservationAct SET Nom=?, Prenom=?, NombrePlaces=?, telephone=?, email=? WHERE IDRes=?",
            [$nom, $prenom, $nombrePlaces, $telephone, $emailUpdate, $id]
        );

        return $this->json(['success' => true]);
    }

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_delete', methods: ['DELETE'])]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $email = $user->getUserIdentifier();

        $existing = $connection->fetchAssociative(
            "SELECT IDRes FROM ReservationAct WHERE IDRes = :id AND email = :email",
            ['id' => $id, 'email' => $email]
        );

        if (!$existing) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $connection->executeStatement(
            "DELETE FROM ReservationAct WHERE IDRes = :id",
            ['id' => $id]
        );

        return $this->json(['success' => true]);
    }
}