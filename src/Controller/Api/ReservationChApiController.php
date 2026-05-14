<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
<<<<<<< HEAD
use Symfony\Component\Security\Core\User\UserInterface;
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

#[Route('/api')]
class ReservationChApiController extends AbstractController
{
<<<<<<< HEAD
    // Méthode utilitaire pour récupérer l'ID de l'utilisateur connecté
    private function getUserId(): ?int
    {
        $user = $this->getUser();
        
        if (!$user instanceof UserInterface) {
            return null;
        }
        
        // Essayer différentes méthodes possibles pour obtenir l'ID
        if (method_exists($user, 'getIdUser')) {
            $id = $user->getIdUser();
            return is_int($id) ? $id : null;
        }
        
        if (method_exists($user, 'getId')) {
            $id = $user->getId();
            return is_int($id) ? $id : null;
        }
        
        // Si aucune méthode standard n'existe, essayer de récupérer via une propriété
        if (property_exists($user, 'id')) {
            $reflection = new \ReflectionProperty($user, 'id');
            $reflection->setAccessible(true);
            $id = $reflection->getValue($user);
            return is_int($id) ? $id : null;
        }
        
        return null;
    }

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    // 1. Récupérer TOUTES les réservations de chambres (uniquement celles de l'utilisateur connecté)
    #[Route('/reservations/chambres', name: 'api_reservations_chambres', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getReservations(Connection $connection): JsonResponse
    {
<<<<<<< HEAD
        $idUtilisateur = $this->getUserId();
        
        if ($idUtilisateur === null) {
            return $this->json(['error' => 'Utilisateur non authentifié ou ID invalide'], 401);
        }
=======
        $user = $this->getUser();
        $idUtilisateur = method_exists($user, 'getId') ? $user->getId() : $user->getIdUser();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        
        $sql = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom, h.ville as hotel_ville
                FROM reservation_chambre r 
                JOIN chambre c ON r.idCh = c.idCh 
                JOIN hotel h ON c.idH = h.idH 
                WHERE r.idUtilisateur = ?
<<<<<<< HEAD
                ORDER BY r.idRes DESC";
        
        $reservations = $connection->fetchAllAssociative($sql, [$idUtilisateur]);
        
        // Convertir les types pour le JSON (par exemple, les IDs en entier)
        array_walk($reservations, function(&$item) {
            foreach ($item as $key => $value) {
                if (is_numeric($value) && strpos($key, 'id') !== false) {
                    $item[$key] = (int)$value;
                }
            }
        });
        
=======
                ORDER BY r.id DESC";
        
        $reservations = $connection->fetchAllAssociative($sql, [$idUtilisateur]);
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return $this->json($reservations);
    }
    
    // 2. Récupérer UNE réservation de chambre par son ID (vérifier qu'elle appartient à l'utilisateur)
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getReservation(Connection $connection, int $id): JsonResponse
    {
<<<<<<< HEAD
        $idUtilisateur = $this->getUserId();
        
        if ($idUtilisateur === null) {
            return $this->json(['error' => 'Utilisateur non authentifié ou ID invalide'], 401);
        }
=======
        $user = $this->getUser();
        $idUtilisateur = method_exists($user, 'getId') ? $user->getId() : $user->getIdUser();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        
        $sql = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom, h.ville as hotel_ville
                FROM reservation_chambre r 
                JOIN chambre c ON r.idCh = c.idCh 
                JOIN hotel h ON c.idH = h.idH 
<<<<<<< HEAD
                WHERE r.idRes = ? AND r.idUtilisateur = ?";
=======
                WHERE r.id = ? AND r.idUtilisateur = ?";
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        
        $reservation = $connection->fetchAssociative($sql, [$id, $idUtilisateur]);
        
        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
<<<<<<< HEAD
        // Convertir les types pour le JSON
        foreach ($reservation as $key => $value) {
            if (is_numeric($value) && (strpos($key, 'id') !== false || strpos($key, 'num') !== false)) {
                $reservation[$key] = (int)$value;
            }
        }
        
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return $this->json($reservation);
    }
    
    // 3. Modifier une réservation de chambre (vérifier qu'elle appartient à l'utilisateur)
<<<<<<< HEAD
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateReservation(Request $request, Connection $connection, int $id): JsonResponse
    {
        $idUtilisateur = $this->getUserId();
        
        if ($idUtilisateur === null) {
            return $this->json(['error' => 'Utilisateur non authentifié ou ID invalide'], 401);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!is_array($data)) {
            return $this->json(['error' => 'Données invalides. Format JSON requis.'], 400);
        }
        
        // Vérifier si la réservation existe et appartient à l'utilisateur
        $checkSql = "SELECT idRes FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?";
        $exists = $connection->fetchOne($checkSql, [$id, $idUtilisateur]);
        
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée ou non autorisée'], 404);
        }
        
        // Validation des données
        $errors = [];
        
        if (isset($data['nom']) && empty(trim($data['nom']))) {
            $errors['nom'] = 'Le nom ne peut pas être vide';
        }
        
        if (isset($data['prenom']) && empty(trim($data['prenom']))) {
            $errors['prenom'] = 'Le prénom ne peut pas être vide';
        }
        
        if (isset($data['telephone']) && empty(trim($data['telephone']))) {
            $errors['telephone'] = 'Le téléphone ne peut pas être vide';
        }
        
        if (isset($data['email'])) {
            if (empty(trim($data['email']))) {
                $errors['email'] = 'L\'email ne peut pas être vide';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'email n\'est pas valide';
            }
        }
        
        if (isset($data['nbre']) && (!is_numeric($data['nbre']) || $data['nbre'] < 1)) {
            $errors['nbre'] = 'Le nombre de personnes doit être un nombre supérieur à 0';
        }
        
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }
        
        // Construire la requête dynamiquement (seulement les champs fournis)
        $updateFields = [];
        $params = [];
        
        if (isset($data['nom'])) {
            $updateFields[] = "nom = ?";
            $params[] = trim($data['nom']);
        }
        
        if (isset($data['prenom'])) {
            $updateFields[] = "prenom = ?";
            $params[] = trim($data['prenom']);
        }
        
        if (isset($data['telephone'])) {
            $updateFields[] = "telephone = ?";
            $params[] = trim($data['telephone']);
        }
        
        if (isset($data['email'])) {
            $updateFields[] = "email = ?";
            $params[] = trim($data['email']);
        }
        
        if (isset($data['nbre'])) {
            $updateFields[] = "nbPersonnes = ?";
            $params[] = (int)$data['nbre'];
        }
        
        if (isset($data['dateDebut'])) {
            $updateFields[] = "dateDebut = ?";
            $params[] = $data['dateDebut'];
        }
        
        if (isset($data['dateFin'])) {
            $updateFields[] = "dateFin = ?";
            $params[] = $data['dateFin'];
        }
        
        if (empty($updateFields)) {
            return $this->json(['error' => 'Aucune donnée à mettre à jour'], 400);
        }
        
        // Ajouter les paramètres pour WHERE
        $params[] = $id;
        $params[] = $idUtilisateur;
        
        // Exécuter la mise à jour
        $sql = "UPDATE reservation_chambre 
                SET " . implode(', ', $updateFields) . "
                WHERE idRes = ? AND idUtilisateur = ?";
        
        $affectedRows = $connection->executeStatement($sql, $params);
        
        if ($affectedRows === 0) {
            return $this->json(['error' => 'Aucune modification effectuée'], 400);
        }
        
        return $this->json([
            'success' => true, 
            'message' => 'Réservation mise à jour avec succès',
            'affected_rows' => $affectedRows
        ]);
=======
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
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }
    
    // 4. Supprimer une réservation de chambre (vérifier qu'elle appartient à l'utilisateur)
    #[Route('/reservations/chambres/{id}', name: 'api_reservation_ch_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteReservation(Connection $connection, int $id): JsonResponse
    {
<<<<<<< HEAD
        $idUtilisateur = $this->getUserId();
        
        if ($idUtilisateur === null) {
            return $this->json(['error' => 'Utilisateur non authentifié ou ID invalide'], 401);
        }
        
        // Vérifier si la réservation existe et appartient à l'utilisateur
        $checkSql = "SELECT idRes FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?";
        $exists = $connection->fetchOne($checkSql, [$id, $idUtilisateur]);
        
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée ou non autorisée'], 404);
        }
        
        // Supprimer la réservation
        $affectedRows = $connection->executeStatement("DELETE FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?", [$id, $idUtilisateur]);
        
        return $this->json([
            'success' => true, 
            'message' => 'Réservation supprimée avec succès',
            'affected_rows' => $affectedRows
        ]);
=======
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
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }
}