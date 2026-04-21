<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * @return Evenement[] Returns all upcoming events
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.DateFin >= :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('e.DateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
