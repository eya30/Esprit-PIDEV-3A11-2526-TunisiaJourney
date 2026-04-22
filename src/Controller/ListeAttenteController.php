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
        
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Connectez-vous pour vous inscrire'], 401);
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

        if ($repo->estDejaInscrit($idActivite, $user->getEmail())) {
            $position = $repo->getPositionDansFile($idActivite, $user->getEmail());
            return $this->json([
                'success' => false, 
                'message' => "Vous êtes déjà en liste d'attente (position {$position})"
            ]);
        }

        $attente = new ListeAttente();
        $attente->setIdActivite($idActivite);
        $attente->setEmailUtilisateur($user->getEmail());
        $attente->setIdUtilisateur($user->getId());
        $attente->setTelephoneUtilisateur($user->getTelephone());
        $attente->setNomUtilisateur($user->getNom());
        $attente->setPrenomUtilisateur($user->getPrenom());
        $attente->setDateInscription(new \DateTime());
        $attente->setStatut(ListeAttente::STATUT_EN_ATTENTE);
        $attente->setTokenConfirmation(bin2hex(random_bytes(32)));

        $em->persist($attente);
        $em->flush();

        $position = $repo->getPositionDansFile($idActivite, $user->getEmail());

        return $this->json([
            'success' => true,
            'message' => "Inscrit en liste d'attente (position {$position})",
            'position' => $position
        ]);
    }

    #[Route('/confirmer/{token}', name: 'liste_attente_confirmer_page', methods: ['GET'])]
    public function pageConfirmation(string $token, ListeAttenteRepository $repo): Response
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
            $repo->getEntityManager()->flush();
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

        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$attente->getIdActivite()]
        );

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
        
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        $estInscrit = $repo->estDejaInscrit($idActivite, $user->getEmail());
        
        if (!$estInscrit) {
            return $this->json(['success' => true, 'inscrit' => false]);
        }

        $position = $repo->getPositionDansFile($idActivite, $user->getEmail());

        return $this->json([
            'success' => true,
            'inscrit' => true,
            'position' => $position
        ]);
    }
}