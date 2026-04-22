<?php

namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CodePromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromo::class);
    }

    /**
     * Trouve un code promo par son code (insensible à la casse)
     * et vérifie qu'il est actif et dans les dates valides.
     */
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
     * Retourne tous les codes promo actifs et valides aujourd'hui.
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
     * Retourne tous les codes promo (actifs et expirés).
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }
}