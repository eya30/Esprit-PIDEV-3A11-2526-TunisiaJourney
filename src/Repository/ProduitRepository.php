<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function getQueryForPagination(?string $search, ?string $cat, string $sort, string $direction): Query
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.titre LIKE :q OR p.description LIKE :q OR p.categorie LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($cat) {
            $qb->andWhere('p.categorie = :cat')
               ->setParameter('cat', $cat);
        }

        $allowedSorts = ['p.idPR', 'p.titre', 'p.prix', 'p.stock', 'p.categorie'];
        if (!in_array($sort, $allowedSorts)) $sort = 'p.idPR';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy($sort, $direction);

        return $qb->getQuery();
    }

    /**
     * @return array<int, Produit>
     */
    public function findBySearchAndSort(?string $search, string $sort, string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.titre LIKE :q OR p.description LIKE :q OR p.categorie LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        $allowedSorts = ['idPR', 'titre', 'prix', 'stock', 'categorie'];
        if (!in_array($sort, $allowedSorts)) $sort = 'idPR';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('p.' . $sort, $direction);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Produit>
     */
    public function findEnRupture(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock <= 0 OR p.disponibilite = false')
            ->orderBy('p.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Produit>
     */
    public function findStockFaible(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock > 0 AND p.stock <= :seuil')
            ->setParameter('seuil', Produit::SEUIL_STOCK_FAIBLE)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Produit>
     */
    public function findNeedsReappro(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock <= :seuil')
            ->setParameter('seuil', Produit::SEUIL_REAPPRO)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }
}