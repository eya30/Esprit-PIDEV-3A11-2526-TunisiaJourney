<?php

namespace App\Controller;

use App\Entity\ListeAttente;
use App\Entity\User;
use App\Repository\ListeAttenteRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/liste-attente')]
class ListeAttenteController extends AbstractController
{
    #[Route('/inscrire/{idActivite}', name: 'liste_attente_inscrire', methods: ['POST'])]
    public function inscrire(
        int $idActivite,
        Request $request,
        Connection $connection,
        EntityManagerInterface $em,
        ListeAttenteRepository $repo
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Connectez-vous pour vous inscrire'], 401);
        }

        // FIX :64 — getId() retourne int|null, setIdUtilisateur() attend string|null
        $userId = $user->getId() !== null ? (string) $user->getId() : null;

        // FIX :53 :54 :63 :75 — getEmail() retourne string|null
        $email = $user->getEmail();
        if ($email === null) {
            return $this->json(['success' => false, 'message' => 'Email utilisateur introuvable'], 400);
        }

        $activite = $connection->fetchAssociative(
            "SELECT a.*, COALESCE(SUM(r.NombrePlaces), 0) as total_reserve 
             FROM Activite a 
             LEFT JOIN ReservationAct r ON r.IDAct = a.IDAct AND r.status = 'confirmé'
             WHERE a.IDAct = ? 
             GROUP BY a.IDAct",
            [$idActivite]
        );

        if (!$activite) {
            return $this->json(['success' => false, 'message' => 'Activité non trouvée'], 404);
        }

        $placesDisponibles = $activite['CapaciteM'] - $activite['total_reserve'];

        if ($placesDisponibles > 0) {
            return $this->json(['success' => false, 'message' => 'Des places sont disponibles, réservez directement']);
        }

        if ($repo->estDejaInscrit($idActivite, $email)) {
            $position = $repo->getPositionDansFile($idActivite, $email);
            return $this->json([
                'success' => false,
                'message' => "Vous êtes déjà en liste d'attente (position {$position})"
            ]);
        }

        $attente = new ListeAttente();
        $attente->setIdActivite($idActivite);
        $attente->setEmailUtilisateur($email);
        $attente->setIdUtilisateur($userId);
        $attente->setTelephoneUtilisateur($user->getTelephone());
        $attente->setNomUtilisateur($user->getNom());
        $attente->setPrenomUtilisateur($user->getPrenom());
        $attente->setDateInscription(new \DateTime());
        $attente->setStatut(ListeAttente::STATUT_EN_ATTENTE);
        $attente->setTokenConfirmation(bin2hex(random_bytes(32)));

        $em->persist($attente);
        $em->flush();

        $position = $repo->getPositionDansFile($idActivite, $email);

        return $this->json([
            'success' => true,
            'message' => "Inscrit en liste d'attente (position {$position})",
            'position' => $position
        ]);
    }

    #[Route('/confirmer/{token}', name: 'liste_attente_confirmer_page', methods: ['GET'])]
    public function pageConfirmation(string $token, ListeAttenteRepository $repo, EntityManagerInterface $em): Response
    {
        $attente = $repo->findOneBy(['tokenConfirmation' => $token]);

        if (!$attente) {
            throw $this->createNotFoundException('Lien invalide');
        }

        if ($attente->getStatut() !== ListeAttente::STATUT_NOTIFIE) {
            $this->addFlash('error', 'Cette invitation n\'est plus valide');
            return $this->redirectToRoute('app_accueil');
        }

        if ($attente->estDelaiDepasse()) {
            $attente->setStatut(ListeAttente::STATUT_EXPIRE);
            // FIX :100 — getEntityManager() est protected, on injecte EntityManagerInterface
            $em->flush();
            $this->addFlash('error', 'Délai de confirmation dépassé (2h)');
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('liste_attente/confirmer.html.twig', [
            'attente' => $attente,
            'token' => $token
        ]);
    }

    #[Route('/api/confirmer/{token}', name: 'liste_attente_confirmer_api', methods: ['POST'])]
    public function confirmerReservation(
        string $token,
        ListeAttenteRepository $repo,
        Connection $connection,
        EntityManagerInterface $em
    ): JsonResponse {
        $attente = $repo->findOneBy(['tokenConfirmation' => $token]);

        if (!$attente) {
            return $this->json(['success' => false, 'message' => 'Lien invalide'], 404);
        }

        if ($attente->getStatut() !== ListeAttente::STATUT_NOTIFIE) {
            return $this->json(['success' => false, 'message' => 'Invitation expirée']);
        }

        if ($attente->estDelaiDepasse()) {
            $attente->setStatut(ListeAttente::STATUT_EXPIRE);
            $em->flush();
            return $this->json(['success' => false, 'message' => 'Délai dépassé']);
        }

        // FIX :151 — fetchAssociative() retourne array|false, vérifier avant d'accéder à 'Prix'
        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$attente->getIdActivite()]
        );

        if (!$activite) {
            return $this->json(['success' => false, 'message' => 'Activité introuvable'], 404);
        }

        $connection->executeStatement(
            "INSERT INTO ReservationAct (id, IDAct, Nom, Prenom, email, telephone, DateReservation, NombrePlaces, Prix, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $attente->getIdUtilisateur(),
                $attente->getIdActivite(),
                $attente->getNomUtilisateur(),
                $attente->getPrenomUtilisateur(),
                $attente->getEmailUtilisateur(),
                $attente->getTelephoneUtilisateur(),
                date('Y-m-d'),
                1,
                $activite['Prix'],
                'confirmé'
            ]
        );

        $attente->setStatut(ListeAttente::STATUT_CONFIRME);
        $attente->setDateConfirmation(new \DateTime());
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Réservation confirmée']);
    }

    #[Route('/verifier-position/{idActivite}', name: 'liste_attente_verifier', methods: ['GET'])]
    public function verifierPosition(int $idActivite, ListeAttenteRepository $repo): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        // FIX :173 :179 — getEmail() retourne string|null
        $email = $user->getEmail();
        if ($email === null) {
            return $this->json(['success' => false, 'message' => 'Email utilisateur introuvable'], 400);
        }

        $estInscrit = $repo->estDejaInscrit($idActivite, $email);

        if (!$estInscrit) {
            return $this->json(['success' => true, 'inscrit' => false]);
        }

        $position = $repo->getPositionDansFile($idActivite, $email);

        return $this->json([
            'success' => true,
            'inscrit' => true,
            'position' => $position
        ]);
    }
}