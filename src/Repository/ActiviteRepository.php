<?php

namespace App\Repository;

use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activite>
 */
class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    /**
     * @return Activite[] Returns activites by evenement ID
     */
    public function findByEvenementId(int $evenementId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.evenement = :evenementId')
            ->setParameter('evenementId', $evenementId)
            ->orderBy('a.HeureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}