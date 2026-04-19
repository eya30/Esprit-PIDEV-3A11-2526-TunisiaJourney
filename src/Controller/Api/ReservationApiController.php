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
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([]);
        }
        
        $email = $user->getUserIdentifier();
        
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
    
    #[Route('/reservations/{id}', name: 'api_reservation_delete', methods: ['DELETE'])]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }
        
        $email = $user->getUserIdentifier();
        
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
    
    // ✅ MODIFICATION UNIQUEMENT : téléphone et nombre de personnes (avec validations strictes)
    #[Route('/reservations/{id}/modify', name: 'api_reservation_modify', methods: ['PUT', 'POST'])]
    public function modifyReservation(Request $request, Connection $connection, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé. Veuillez vous connecter.'], 401);
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
        
        $errors = [];
        $updates = [];
        $params = ['id' => $id];
        
        // ✅ VALIDATION TELEPHONE : exactement 8 chiffres, pas de lettres
        $telephone = $data['telephone'] ?? null;
        if ($telephone !== null) {
            // Nettoyer le numéro (enlever espaces, tirets, etc.)
            $telephone = preg_replace('/[^0-9]/', '', $telephone);
            
            if (strlen($telephone) !== 8) {
                $errors[] = 'Le téléphone doit contenir exactement 8 chiffres.';
            } elseif (!preg_match('/^[0-9]{8}$/', $telephone)) {
                $errors[] = 'Le téléphone ne doit contenir que des chiffres.';
            } else {
                $updates[] = "telephone = :telephone";
                $params['telephone'] = $telephone;
            }
        }
        
        // ✅ VALIDATION NOMBRE DE PERSONNES : entre 1 et 20, entier
        $nbre = $data['nbre'] ?? null;
        if ($nbre !== null) {
            if (!is_numeric($nbre)) {
                $errors[] = 'Le nombre de personnes doit être un nombre.';
            } else {
                $nbre = (int)$nbre;
                if ($nbre < 1) {
                    $errors[] = 'Le nombre de personnes doit être au minimum 1.';
                } elseif ($nbre > 20) {
                    $errors[] = 'Le nombre de personnes ne peut pas dépasser 20.';
                } else {
                    $updates[] = "nbre = :nbre";
                    $params['nbre'] = $nbre;
                    
                    // Recalculer le prix si le nombre change
                    $reservation = $connection->fetchAssociative("
                        SELECT r.prixProg, p.idProg, v.prix 
                        FROM reservationprog r
                        LEFT JOIN programmes p ON r.idP = p.idProg
                        LEFT JOIN voyages v ON p.idV = v.idV
                        WHERE r.idRP = :id
                    ", ['id' => $id]);
                    
                    if ($reservation && isset($reservation['prix']) && $reservation['prix'] > 0) {
                        $newPrix = $reservation['prix'] * $nbre;
                        $updates[] = "prixProg = :prixProg";
                        $params['prixProg'] = $newPrix;
                    }
                }
            }
        }
        
        // ❌ EMPÊCHER la modification d'autres champs (nom, prenom, email, etc.)
        $forbiddenFields = ['nom', 'prenom', 'email', 'idP', 'dateProgramme', 'statutPaiement', 'stripeSessionId', 'userId'];
        foreach ($forbiddenFields as $field) {
            if (array_key_exists($field, $data)) {
                $errors[] = "Le champ '$field' ne peut pas être modifié. Seuls le téléphone et le nombre de personnes sont modifiables.";
            }
        }
        
        // Retourner les erreurs si présentes
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }
        
        // Aucune modification demandée
        if (empty($updates)) {
            return $this->json([
                'success' => false,
                'message' => 'Aucune modification valide. Veuillez fournir un téléphone (8 chiffres) ou un nombre de personnes (1-20).'
            ], 400);
        }
        
        // Exécuter la mise à jour
        $sql = "UPDATE reservationprog SET " . implode(', ', $updates) . " WHERE idRP = :id";
        $connection->executeStatement($sql, $params);
        
        // Récupérer la réservation mise à jour
        $updatedReservation = $connection->fetchAssociative(
            "SELECT * FROM reservationprog WHERE idRP = :id",
            ['id' => $id]
        );
        
        return $this->json([
            'success' => true,
            'message' => 'Réservation modifiée avec succès',
            'modified' => $updates,
            'reservation' => $updatedReservation
        ]);
    }
}