<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationChController extends AbstractController
{
    #[Route('/chambre/new/{idCh}', name: 'app_reservation_ch_new', methods: ['POST'])]
    public function new(Request $request, Connection $connection, $idCh = null): Response
    {
        if (!$idCh) {
            $this->addFlash('error', 'ID de la chambre manquant');
            return $this->redirectToRoute('app_hotel_index');
        }

        // Récupérer la chambre
        $sqlChambre = "SELECT c.*, h.nom as hotel_nom, h.etoiles as hotel_etoiles, h.ville as hotel_ville, h.idH as hotel_id, h.promotion 
                       FROM chambre c 
                       JOIN hotel h ON c.idH = h.idH 
                       WHERE c.idCh = ?";
        $chambre = $connection->fetchAssociative($sqlChambre, [$idCh]);

        if (!$chambre) {
            $this->addFlash('error', 'Chambre non trouvée');
            return $this->redirectToRoute('app_hotel_index');
        }

        $data = $request->request->all();
        
        // Récupération des données
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $email = trim($data['email'] ?? '');
        $telephone = trim($data['telephone'] ?? '');
        $dateDebut = $data['dateDebut'] ?? '';
        $dateFin = $data['dateFin'] ?? '';
        $nbPersonnes = $data['nbPersonnes'] ?? 1;

        // ID utilisateur : si l'utilisateur est connecté, on récupère son id depuis la session
        $idUtilisateur = $data['idUtilisateur'] ?? null;
        if (is_string($idUtilisateur) && trim($idUtilisateur) === '') {
            $idUtilisateur = null;
        }

        $user = $this->getUser();
        if ($user) {
            if (method_exists($user, 'getId')) {
                $idUtilisateur = $user->getId();
            }
        }

        // fallback temporaire
        if ($idUtilisateur === null) {
            $idUtilisateur = 1;
        }

        $formData = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'nbPersonnes' => $nbPersonnes
        ];

        $errors = [];
        $calcul = [];

        // Validations (Nom, Prénom, Email, Téléphone, Dates, etc.)
        if (empty($nom)) {
            $errors[] = "Le nom est requis.";
        } elseif (strlen($nom) < 2) {
            $errors[] = "Le nom doit contenir au moins 2 caractères.";
        } elseif (strlen($nom) > 50) {
            $errors[] = "Le nom ne peut pas dépasser 50 caractères.";
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/', $nom)) {
            $errors[] = "Le nom ne doit contenir que des lettres.";
        }

        if (empty($prenom)) {
            $errors[] = "Le prénom est requis.";
        } elseif (strlen($prenom) < 2) {
            $errors[] = "Le prénom doit contenir au moins 2 caractères.";
        } elseif (strlen($prenom) > 50) {
            $errors[] = "Le prénom ne peut pas dépasser 50 caractères.";
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/', $prenom)) {
            $errors[] = "Le prénom ne doit contenir que des lettres.";
        }

        if (empty($email)) {
            $errors[] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide.";
        } elseif (strlen($email) > 100) {
            $errors[] = "L'email ne peut pas dépasser 100 caractères.";
        }

        if (empty($telephone)) {
            $errors[] = "Le téléphone est requis.";
        } else {
            $telephoneClean = preg_replace('/[^0-9]/', '', $telephone);
            if (strlen($telephoneClean) < 8) {
                $errors[] = "Le téléphone doit contenir au moins 8 chiffres.";
            } elseif (strlen($telephoneClean) > 15) {
                $errors[] = "Le téléphone ne peut pas dépasser 15 chiffres.";
            } elseif (!preg_match('/^[0-9+\-\s]+$/', $telephone)) {
                $errors[] = "Le téléphone contient des caractères non autorisés.";
            }
        }

        if (empty($dateDebut)) {
            $errors[] = "La date de début est requise.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) {
            $errors[] = "La date de début doit être au format AAAA-MM-JJ.";
        } else {
            $dateDebutObj = new \DateTime($dateDebut);
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            
            if ($dateDebutObj < $today) {
                $errors[] = "La date de début ne peut pas être dans le passé.";
            }
            
            $maxDate = new \DateTime('+2 years');
            if ($dateDebutObj > $maxDate) {
                $errors[] = "La date de début ne peut pas être au-delà de 2 ans.";
            }
        }

        if (empty($dateFin)) {
            $errors[] = "La date de fin est requise.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
            $errors[] = "La date de fin doit être au format AAAA-MM-JJ.";
        } else {
            $dateFinObj = new \DateTime($dateFin);
            $maxDate = new \DateTime('+2 years');
            if ($dateFinObj > $maxDate) {
                $errors[] = "La date de fin ne peut pas être au-delà de 2 ans.";
            }
        }

        $nbNuit = 0;
        $prixTotal = 0;
        $prixParNuitApresPromo = $chambre['prix_nuit'];
        $promotion = $chambre['promotion'] ?? 0;

        if ($promotion > 0) {
            $prixParNuitApresPromo = $prixParNuitApresPromo * (1 - $promotion / 100);
        }

        if (empty($errors) && !empty($dateDebut) && !empty($dateFin)) {
            $dateDebutObj = new \DateTime($dateDebut);
            $dateFinObj = new \DateTime($dateFin);
            
            if ($dateFinObj <= $dateDebutObj) {
                $errors[] = "La date de fin doit être postérieure à la date de début.";
            } else {
                $interval = $dateDebutObj->diff($dateFinObj);
                $nbNuit = $interval->days;
                
                if ($nbNuit < 1) {
                    $errors[] = "Le séjour doit durer au moins 1 nuit.";
                }
                if ($nbNuit > 90) {
                    $errors[] = "Le séjour ne peut pas dépasser 90 nuits (environ 3 mois).";
                }
                
                $prixTotal = $prixParNuitApresPromo * $nbNuit;
                
                $calcul = [
                    'prix_nuit' => $prixParNuitApresPromo,
                    'nb_nuits' => $nbNuit,
                    'prix_total' => $prixTotal,
                    'promotion' => $promotion
                ];
            }
        }

        if (empty($nbPersonnes)) {
            $errors[] = "Le nombre de personnes est requis.";
        } elseif (!is_numeric($nbPersonnes)) {
            $errors[] = "Le nombre de personnes doit être un nombre.";
        } elseif ($nbPersonnes <= 0) {
            $errors[] = "Le nombre de personnes doit être au moins 1.";
        } elseif ($nbPersonnes > $chambre['capacite_max']) {
            $errors[] = "Le nombre de personnes ne peut pas dépasser " . $chambre['capacite_max'] . " personnes.";
        } elseif ($nbPersonnes > 10) {
            $errors[] = "Le nombre de personnes ne peut pas dépasser 10.";
        }

        if (empty($errors) && !empty($dateDebut) && !empty($dateFin)) {
            $sqlCheck = "SELECT COUNT(*) as count FROM reservation_chambre 
                         WHERE idCh = ? AND statut != 'annulé'
                         AND ((dateDebut <= ? AND dateFin >= ?) 
                              OR (dateDebut BETWEEN ? AND ?) 
                              OR (dateFin BETWEEN ? AND ?))";
            
            $existing = $connection->fetchAssociative($sqlCheck, [
                $idCh, $dateFin, $dateDebut, $dateDebut, $dateFin, $dateDebut, $dateFin
            ]);
            
            if ($existing && $existing['count'] > 0) {
                $errors[] = "Cette chambre est déjà réservée pour les dates sélectionnées.";
            }
        }

        if (count($errors) > 0) {
            return $this->render('chambre/show.html.twig', [
                'chambre'  => $chambre,
                'errors'   => $errors,
                'formData' => $formData,
                'calcul'   => $calcul
            ]);
        }

        $detailsPrix = sprintf('%d nuit(s) x %.2f €', $nbNuit, $prixParNuitApresPromo);
        if ($promotion > 0) {
            $detailsPrix .= sprintf(' (promotion: -%.0f%%)', $promotion);
        }
        $detailsPrix .= sprintf(' = %.2f €', $prixTotal);
        
        $sqlInsert = "INSERT INTO reservation_chambre 
                      (idCh, idUtilisateur, dateDebut, dateFin, nbNuit, prixTotal, nbPersonnes, detailsPrix, telephone, statut, nom, prenom, email) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmé', ?, ?, ?)";
        
        $connection->executeStatement($sqlInsert, [
            $idCh,
            $idUtilisateur,
            $dateDebut,
            $dateFin,
            $nbNuit,
            $prixTotal,
            $nbPersonnes,
            $detailsPrix,
            $telephone,
            $nom,
            $prenom,
            $email
        ]);

        $this->addFlash('success', '✅ Réservation confirmée ! Merci ' . $nom . ' ' . $prenom . '.');
        return $this->redirectToRoute('app_chambre_show', ['idCh' => $idCh]);
    }

    // API Routes
    #[Route('/api/reservations/all', name: 'api_reservations_all', methods: ['GET'])]
    public function apiGetAll(Connection $connection): Response
    {
        $user = $this->getUser();
        $idUtilisateur = $user && method_exists($user, 'getId') ? $user->getId() : 1;
        
        // Récupérer les réservations de chambres
    $sqlChambres = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom 
            FROM reservation_chambre r 
            JOIN chambre c ON r.idCh = c.idCh 
            JOIN hotel h ON c.idH = h.idH 
            WHERE r.idUtilisateur = ? 
            ORDER BY r.idRes DESC";
        $chambres = $connection->fetchAllAssociative($sqlChambres, [$idUtilisateur]);
        
        foreach ($chambres as &$res) {
            $res['type'] = 'chambre';
        }
        
        return $this->json($chambres);
    }

    #[Route('/api/reservations/chambres/{id}', name: 'api_reservations_chambres_get', methods: ['GET'])]
    public function apiGetOne(Connection $connection, $id): Response
    {
        $user = $this->getUser();
        $idUtilisateur = $user && method_exists($user, 'getId') ? $user->getId() : 1;

    $sql = "SELECT * FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?";
    $reservation = $connection->fetchAssociative($sql, [$id, $idUtilisateur]);
        
        if (!$reservation) {
            return $this->json(['error' => 'Non trouvé'], 404);
        }
        
        return $this->json($reservation);
    }

    #[Route('/api/reservations/chambres/{id}', name: 'api_reservations_chambres_delete', methods: ['DELETE'])]
    public function apiDelete(Connection $connection, $id): Response
    {
        $user = $this->getUser();
        $idUtilisateur = $user && method_exists($user, 'getId') ? $user->getId() : 1;

    $sql = "DELETE FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?";
    $affected = $connection->executeStatement($sql, [$id, $idUtilisateur]);
        
        if ($affected > 0) {
            return $this->json(['success' => true]);
        }
        
        return $this->json(['error' => 'Non trouvé'], 404);
    }

    #[Route('/api/reservations/chambres/{id}', name: 'api_reservations_chambres_put', methods: ['PUT'])]
    public function apiUpdate(Connection $connection, Request $request, $id): Response
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $idUtilisateur = $user && method_exists($user, 'getId') ? $user->getId() : 1;
        
        // Vérifier que la réservation existe
    $checkSql = "SELECT idRes FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?";
    $exists = $connection->fetchOne($checkSql, [$id, $idUtilisateur]);
        
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
        
    $sql = "UPDATE reservation_chambre 
        SET nom = ?, prenom = ?, telephone = ?, email = ?, nbPersonnes = ?
        WHERE idRes = ? AND idUtilisateur = ?";
        
        $affected = $connection->executeStatement($sql, [
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['telephone'] ?? '',
            $data['email'] ?? '',
            $data['nbre'] ?? 1,
            $id,
            $idUtilisateur
        ]);
        
        if ($affected > 0) {
            return $this->json(['success' => true]);
        }
        
        return $this->json(['error' => 'Aucune modification'], 400);
    }
}