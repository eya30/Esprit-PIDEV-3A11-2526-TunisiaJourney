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

<<<<<<< HEAD
    /**
     * @return ReservationProg[]
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function findReservationsByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateProgramme', 'DESC')
            ->getQuery()
            ->getResult();
    }

<<<<<<< HEAD
    /**
     * @return ReservationProg[]
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function findReservationsByProgramme(string $programmeId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.programme = :programmeId')
            ->setParameter('programmeId', $programmeId)
            ->orderBy('r.dateProgramme', 'DESC')
            ->getQuery()
            ->getResult();
    }

<<<<<<< HEAD
    /**
     * @return ReservationProg[]
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD

        return (float) ($result ?? 0);
=======
        
        return $result ?: 0;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }

    public function getReservationsCountByPeriod(\DateTime $start, \DateTime $end): int
    {
<<<<<<< HEAD
        return (int) $this->createQueryBuilder('r')
=======
        return $this->createQueryBuilder('r')
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            ->select('COUNT(r.idRP)')
            ->where('r.dateProgramme BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
