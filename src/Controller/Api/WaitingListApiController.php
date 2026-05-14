<?php

namespace App\Controller\Api;

use App\Entity\ListeAttente;
use App\Entity\User;
use App\Repository\ListeAttenteRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/liste-attente')]
class WaitingListApiController extends AbstractController
{
    #[Route('/statut/{idActivite}', name: 'api_liste_attente_statut', methods: ['GET'])]
    public function getStatut(int $idActivite, ListeAttenteRepository $repo, Connection $connection): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $placesReservees = (int) $connection->fetchOne(
            "SELECT COALESCE(SUM(NombrePlaces), 0) FROM ReservationAct WHERE IDAct = ? AND status = 'confirmé'",
            [$idActivite]
        );

        $activite = $connection->fetchAssociative(
            "SELECT CapaciteM FROM Activite WHERE IDAct = ?",
            [$idActivite]
        );

        // :33 fixed — fetchAssociative() returns array|false
        $placesDisponibles = ($activite !== false) ? ((int) $activite['CapaciteM'] - $placesReservees) : 0;
        $estComplet = $placesDisponibles <= 0;

        $data = [
            'estComplet'        => $estComplet,
            'placesDisponibles' => $placesDisponibles,
            'estConnecte'       => $user !== null,
        ];

        // :43 / :47 fixed — getEmail() returns string|null
        if ($user !== null && $estComplet) {
            $email = $user->getEmail();
            if ($email !== null) {
                $estInscrit = $repo->estDejaInscrit($idActivite, $email);
                $data['estInscrit'] = $estInscrit;

                if ($estInscrit) {
                    $data['position'] = $repo->getPositionDansFile($idActivite, $email);
                }
            }
        }

        return $this->json($data);
    }

    #[Route('/nombre-attente/{idActivite}', name: 'api_liste_attente_nombre', methods: ['GET'])]
    public function getNombreEnAttente(int $idActivite, ListeAttenteRepository $repo): JsonResponse
    {
        $nombre = $repo->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.idActivite = :idActivite')
            ->andWhere('l.statut IN (:statuts)')
            ->setParameter('idActivite', $idActivite)
            ->setParameter('statuts', [ListeAttente::STATUT_EN_ATTENTE, ListeAttente::STATUT_NOTIFIE])
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json(['nombre' => $nombre]);
    }

    #[Route('/annuler/{id}', name: 'api_liste_attente_annuler', methods: ['DELETE'])]
    public function annulerInscription(int $id, ListeAttenteRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $attente = $repo->find($id);

        if (!$attente) {
            return $this->json(['success' => false, 'message' => 'Inscription non trouvée'], 404);
        }

        if ($attente->getEmailUtilisateur() !== $user->getEmail()) {
            return $this->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if ($attente->getStatut() !== ListeAttente::STATUT_EN_ATTENTE) {
            return $this->json(['success' => false, 'message' => "Impossible d'annuler, vous avez déjà été notifié"], 400);
        }

        $attente->setStatut(ListeAttente::STATUT_ANNULE);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Inscription annulée']);
    }

    #[Route('/mes-inscriptions', name: 'api_liste_attente_mes_inscriptions', methods: ['GET'])]
    public function getMesInscriptions(ListeAttenteRepository $repo, Connection $connection): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([]);
        }

        // :109 fixed — guard against null email before passing to repository
        $email = $user->getEmail();
        if ($email === null) {
            return $this->json([]);
        }

        $inscriptions = $repo->trouverParEmail($email);

        $data = [];
        foreach ($inscriptions as $inscription) {
            $activite = $connection->fetchAssociative(
                "SELECT Titre FROM Activite WHERE IDAct = ?",
                [$inscription->getIdActivite()]
            );

            // :122 fixed — null-safe operator on nullable DateTimeInterface
            $dateInscription = $inscription->getDateInscription()?->format('d/m/Y H:i');
            $dateLimite      = $inscription->getDateLimiteConfirmation()?->format('d/m/Y H:i');

            // :125 fixed — guard against null idActivite and emailUtilisateur
            $idActivite       = $inscription->getIdActivite();
            $emailUtilisateur = $inscription->getEmailUtilisateur();

            $position = null;
            if (
                $inscription->getStatut() === ListeAttente::STATUT_EN_ATTENTE
                && $idActivite !== null
                && $emailUtilisateur !== null
            ) {
                $position = $repo->getPositionDansFile($idActivite, $emailUtilisateur);
            }

            $data[] = [
                'id'              => $inscription->getId(),
                'idActivite'      => $idActivite,
                'titreActivite'   => ($activite !== false ? $activite['Titre'] : null) ?? 'Activité',
                'dateInscription' => $dateInscription,
                'statut'          => $inscription->getStatut(),
                'position'        => $position,
                'dateLimite'      => $dateLimite,
            ];
        }

        return $this->json($data);
    }
}