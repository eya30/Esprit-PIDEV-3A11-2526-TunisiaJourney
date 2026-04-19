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
     * @return ReservationAct[] Returns reservations by activite ID
     */
    public function findByActiviteId(int $activiteId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.IDAct = :activiteId')
            ->setParameter('activiteId', $activiteId)
            ->orderBy('r.IDRes', 'DESC')
            ->getQuery()
            ->getResult();
    }
}