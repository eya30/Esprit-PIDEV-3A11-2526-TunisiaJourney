<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\ReservationAct;

#[Route('/api')]
class ReservationActApiController extends AbstractController
{
    /**
     * Retourne l'email de l'utilisateur connecté (cast vers App\Entity\User),
     * ou une JsonResponse 401 si non authentifié.
     */
    private function getUserEmail(): string|JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

<<<<<<< HEAD
        $email = $user->getEmail();
        if ($email === null) {
            return new JsonResponse(['error' => 'Email utilisateur introuvable'], 401);
        }

        return $email;
=======
        return $user->getEmail();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }

    // ── Lister les réservations CONFIRMÉES de l'utilisateur connecté ─────────

    #[Route('/reservations-act', name: 'api_reservations_act', methods: ['GET'])]
    public function getReservations(Connection $connection): JsonResponse
    {
        $email = $this->getUserEmail();
        if ($email instanceof JsonResponse) return $email;

<<<<<<< HEAD
=======
        // MODIFICATION : Ne retourner que les réservations avec status = 'confirmé'
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $reservations = $connection->fetchAllAssociative(
            "SELECT * FROM ReservationAct WHERE Email = ? AND status = 'confirmé' ORDER BY IDRes DESC",
            [$email]
        );

        return $this->json($reservations);
    }

    // ── Récupérer une réservation (vérification propriétaire) ────────────────

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_get', methods: ['GET'])]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
        $email = $this->getUserEmail();
        if ($email instanceof JsonResponse) return $email;

        $reservation = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = ? AND Email = ? AND status = 'confirmé'",
            [$id, $email]
        );

        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }

        return $this->json($reservation);
    }

    // ── Modifier une réservation (vérification propriétaire) ─────────────────

    #[Route('/reservations-act/{id}', name: 'api_reservation_act_update', methods: ['PUT'])]
    public function updateReservation(
        Request $request,
        Connection $connection,
        ValidatorInterface $validator,
        int $id
    ): JsonResponse {
        $email = $this->getUserEmail();
        if ($email instanceof JsonResponse) return $email;

        $data = json_decode($request->getContent(), true);

<<<<<<< HEAD
=======
        // MODIFICATION : Vérifier aussi que la réservation n'est pas annulée
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $existing = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = ? AND Email = ? AND status = 'confirmé'",
            [$id, $email]
        );

        if (!$existing) {
            return $this->json(['error' => 'Réservation non trouvée ou annulée'], 404);
        }

        $reservation = new ReservationAct();
        $reservation->setTelephone($data['telephone'] ?? $existing['telephone']);
        $reservation->setEmail($data['email']         ?? $existing['Email']);
        $reservation->setNombrePlaces($data['nbre']   ?? $existing['NombrePlaces']);
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
            "UPDATE ReservationAct SET telephone = ?, Email = ?, NombrePlaces = ? WHERE IDRes = ? AND Email = ? AND status = 'confirmé'",
            [
                $reservation->getTelephone(),
                $reservation->getEmail(),
                $reservation->getNombrePlaces(),
                $id,
                $email,
            ]
        );

        return $this->json(['success' => true]);
    }
<<<<<<< HEAD
}
=======

   
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
