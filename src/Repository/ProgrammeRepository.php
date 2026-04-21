<?php

namespace App\Repository;

use App\Entity\Programme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Programme>
 */
class ProgrammeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Programme::class);
    }

    /**
     * @return Programme[] Returns an array of Programme objects
     */
    public function findByVoyageId(int $voyageId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.idV = :voyageId')
            ->setParameter('voyageId', $voyageId)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}