<?php

namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
/**
 * @extends ServiceEntityRepository<CodePromo>
 */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
class CodePromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromo::class);
    }

<<<<<<< HEAD
=======
    /**
     * Trouve un code promo par son code (insensible à la casse)
     * et vérifie qu'il est actif et dans les dates valides.
     */
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
     * @return CodePromo[]
=======
     * Retourne tous les codes promo actifs et valides aujourd'hui.
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
     * @return CodePromo[]
=======
     * Retourne tous les codes promo (actifs et expirés).
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }
}