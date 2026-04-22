<?php

namespace App\Repository;

use App\Entity\ReservationAct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationAct>
 */
class ReservationActRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationAct::class);
    }

    /**
     * @return ReservationAct[] Returns reservations by activite ID (UNIQUEMENT LES CONFIRMÉES)
     */
    public function findByActiviteId(int $activiteId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.IDAct = :activiteId')
            ->andWhere('r.status = :status')
            ->setParameter('activiteId', $activiteId)
            ->setParameter('status', ReservationAct::STATUS_CONFIRMED)
            ->orderBy('r.IDRes', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ReservationAct[] Returns ALL reservations by activite ID (INCLUT LES ANNULÉES)
     */
    public function findByActiviteIdIncludingCancelled(int $activiteId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.IDAct = :activiteId')
            ->setParameter('activiteId', $activiteId)
            ->orderBy('r.IDRes', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Soft delete : annuler une réservation
     */
    public function cancelReservation(ReservationAct $reservation): void
    {
        $reservation->setStatus(ReservationAct::STATUS_CANCELLED);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère le nombre total de places réservées et confirmées pour une activité
     */
    public function getTotalConfirmedPlacesByActiviteId(int $activiteId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.NombrePlaces), 0)')
            ->andWhere('r.IDAct = :activiteId')
            ->andWhere('r.status = :status')
            ->setParameter('activiteId', $activiteId)
            ->setParameter('status', ReservationAct::STATUS_CONFIRMED)
            ->getQuery()
            ->getSingleScalarResult();
    }
} 