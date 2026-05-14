<?php

namespace App\Repository;

use App\Entity\ReservationProg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationProg>
 */
class ReservationProgRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationProg::class);
    }

    /**
     * @return ReservationProg[]
     */
    public function findReservationsByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateProgramme', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ReservationProg[]
     */
    public function findReservationsByProgramme(string $programmeId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.programme = :programmeId')
            ->setParameter('programmeId', $programmeId)
            ->orderBy('r.dateProgramme', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ReservationProg[]
     */
    public function findPaidReservations(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.statutPaiement = :statut')
            ->setParameter('statut', 'payé')
            ->orderBy('r.dateProgramme', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.prixProg) as total')
            ->where('r.statutPaiement = :statut')
            ->setParameter('statut', 'payé')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getReservationsCountByPeriod(\DateTime $start, \DateTime $end): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.idRP)')
            ->where('r.dateProgramme BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}