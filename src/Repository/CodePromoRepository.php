<?php

namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CodePromo>
 */
class CodePromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromo::class);
    }

    public function findCodeValide(string $code): ?CodePromo
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('c')
            ->where('LOWER(c.code) = LOWER(:code)')
            ->andWhere('c.statut = :statut')
            ->andWhere('c.dateDebut <= :today')
            ->andWhere('c.dateFin >= :today')
            ->setParameter('code', $code)
            ->setParameter('statut', 'actif')
            ->setParameter('today', $today)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return CodePromo[]
     */
    public function findAllValides(): array
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->andWhere('c.dateDebut <= :today')
            ->andWhere('c.dateFin >= :today')
            ->setParameter('statut', 'actif')
            ->setParameter('today', $today)
            ->orderBy('c.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CodePromo[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }
}