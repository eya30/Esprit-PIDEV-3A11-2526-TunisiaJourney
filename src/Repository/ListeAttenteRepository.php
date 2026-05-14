<?php

namespace App\Repository;

use App\Entity\ListeAttente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeAttente>
 */
class ListeAttenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeAttente::class);
    }

    public function trouverProchainEnAttente(int $idActivite): ?ListeAttente
    {
        return $this->createQueryBuilder('l')
            ->where('l.idActivite = :idActivite')
            ->andWhere('l.statut = :statut')
            ->setParameter('idActivite', $idActivite)
            ->setParameter('statut', ListeAttente::STATUT_EN_ATTENTE)
            ->orderBy('l.dateInscription', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ListeAttente[]
     */
    public function trouverParEmail(string $email): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.emailUtilisateur = :email')
            ->orderBy('l.dateInscription', 'DESC')
            ->setParameter('email', $email)
            ->getQuery()
            ->getResult();
    }

    public function getPositionDansFile(int $idActivite, string $email): int
    {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.idActivite = :idActivite')
            ->andWhere('l.statut IN (:statuts)')
            ->andWhere('l.dateInscription <= (SELECT l2.dateInscription FROM App\Entity\ListeAttente l2 WHERE l2.emailUtilisateur = :email AND l2.idActivite = :idActivite)')
            ->setParameter('idActivite', $idActivite)
            ->setParameter('statuts', [ListeAttente::STATUT_EN_ATTENTE, ListeAttente::STATUT_NOTIFIE])
            ->setParameter('email', $email);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return ListeAttente[]
     */
    public function trouverInscriptionsExpirees(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('l')
            ->where('l.statut = :statutNotifie')
            ->andWhere('l.dateLimiteConfirmation < :now')
            ->setParameter('statutNotifie', ListeAttente::STATUT_NOTIFIE)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function estDejaInscrit(int $idActivite, string $email): bool
    {
        $result = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.idActivite = :idActivite')
            ->andWhere('l.emailUtilisateur = :email')
            ->andWhere('l.statut IN (:statuts)')
            ->setParameter('idActivite', $idActivite)
            ->setParameter('email', $email)
            ->setParameter('statuts', [ListeAttente::STATUT_EN_ATTENTE, ListeAttente::STATUT_NOTIFIE])
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function compterPlacesReserveesConfirmees(int $idActivite): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT COALESCE(SUM(NombrePlaces), 0) FROM ReservationAct WHERE IDAct = :idActivite AND status = "confirmé"';
        return (int) $conn->fetchOne($sql, ['idActivite' => $idActivite]);
    }
}