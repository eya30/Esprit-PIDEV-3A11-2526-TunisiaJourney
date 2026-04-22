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

        $placesDisponibles = $activite['CapaciteM'] - $placesReservees;
        $estComplet = $placesDisponibles <= 0;

        $data = [
            'estComplet' => $estComplet,
            'placesDisponibles' => $placesDisponibles,
            'estConnecte' => $user !== null,
        ];

        if ($user && $estComplet) {
            $estInscrit = $repo->estDejaInscrit($idActivite, $user->getEmail());
            $data['estInscrit'] = $estInscrit;
            
            if ($estInscrit) {
                $data['position'] = $repo->getPositionDansFile($idActivite, $user->getEmail());
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
            return $this->json(['success' => false, 'message' => 'Impossible d\'annuler, vous avez déjà été notifié'], 400);
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

        $inscriptions = $repo->trouverParEmail($user->getEmail());

        $data = [];
        foreach ($inscriptions as $inscription) {
            $activite = $connection->fetchAssociative(
                "SELECT Titre FROM Activite WHERE IDAct = ?",
                [$inscription->getIdActivite()]
            );

            $data[] = [
                'id' => $inscription->getId(),
                'idActivite' => $inscription->getIdActivite(),
                'titreActivite' => $activite['Titre'] ?? 'Activité',
                'dateInscription' => $inscription->getDateInscription()->format('d/m/Y H:i'),
                'statut' => $inscription->getStatut(),
                'position' => $inscription->getStatut() === ListeAttente::STATUT_EN_ATTENTE ? 
                    $repo->getPositionDansFile($inscription->getIdActivite(), $inscription->getEmailUtilisateur()) : null,
                'dateLimite' => $inscription->getDateLimiteConfirmation() ? 
                    $inscription->getDateLimiteConfirmation()->format('d/m/Y H:i') : null,
            ];
        }

        return $this->json($data);
    }
}