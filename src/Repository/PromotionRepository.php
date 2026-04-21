<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    // Trouver une promotion par code promo valide
    public function findOneByCodeValide(string $code): ?Promotion
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('p')
            ->where('p.codePromo = :code')
            ->andWhere('p.actif = true')
            ->andWhere('p.dateDebut <= :now OR p.dateDebut IS NULL')
            ->andWhere('p.dateFin >= :now OR p.dateFin IS NULL')
            ->setParameter('code', $code)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Promotions par quantité (type 'quantite')
    public function findActiveQuantityPromotions(): array
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.actif = true')
            ->andWhere('p.dateDebut <= :now OR p.dateDebut IS NULL')
            ->andWhere('p.dateFin >= :now OR p.dateFin IS NULL')
            ->setParameter('type', 'quantite')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}