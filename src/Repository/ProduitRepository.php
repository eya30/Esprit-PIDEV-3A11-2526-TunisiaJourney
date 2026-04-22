<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    // ✅ Pour la pagination admin avec recherche et tri
    public function getQueryForPagination(?string $search, ?string $cat, string $sort, string $direction)
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

    // ✅ Recherche + tri pour le front
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

    // ✅ Produits en rupture de stock
    public function findEnRupture(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock <= 0 OR p.disponibilite = false')
            ->orderBy('p.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ✅ Produits avec stock faible (≤ seuil)
    public function findStockFaible(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock > 0 AND p.stock <= :seuil')
            ->setParameter('seuil', Produit::SEUIL_STOCK_FAIBLE)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ✅ Produits nécessitant réapprovisionnement (stock ≤ seuil réappro)
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