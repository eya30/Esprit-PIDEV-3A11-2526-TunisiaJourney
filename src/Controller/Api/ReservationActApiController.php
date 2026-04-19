<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\ReservationAct;

#[Route('/api')]
class ReservationActApiController extends AbstractController
{
    #[Route('/reservations-act', name: 'api_reservations_act', methods: ['GET'])]
    public function getReservations(Connection $connection): JsonResponse
    {
        $reservations = $connection->fetchAllAssociative(
            "SELECT * FROM ReservationAct ORDER BY IDRes DESC"
        );
        return $this->json($reservations);
    }

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_get', methods: ['GET'])]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
        $reservation = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = ?", [$id]
        );

        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }

        return $this->json($reservation);
    }

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_update', methods: ['PUT'])]
    public function updateReservation(Request $request, Connection $connection, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $existing = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = ?", [$id]
        );

        if (!$existing) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }

        $reservation = new ReservationAct();
        $reservation->setTelephone($data['telephone'] ?? $existing['telephone']);
        $reservation->setEmail($data['email'] ?? $existing['email']);
        $reservation->setNombrePlaces($data['nbre'] ?? $existing['NombrePlaces']);
        $reservation->setNom($existing['Nom']);
        $reservation->setPrenom($existing['Prenom']);

        $errors = $validator->validate($reservation);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $propertyPath = $error->getPropertyPath();
                if ($propertyPath === 'nombrePlaces') {
                    $propertyPath = 'nbre';
                }
                $errorMessages[$propertyPath] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $connection->executeStatement(
            "UPDATE ReservationAct SET telephone=?, email=?, NombrePlaces=? WHERE IDRes=?",
            [
                $reservation->getTelephone(),
                $reservation->getEmail(),
                $reservation->getNombrePlaces(),
                $id
            ]
        );

        return $this->json(['success' => true]);
    }

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_delete', methods: ['DELETE'])]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
        $connection->executeStatement(
            "DELETE FROM ReservationAct WHERE IDRes = ?", [$id]
        );
        return $this->json(['success' => true]);
    }

    
}