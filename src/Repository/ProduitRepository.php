<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    /**
 * Méthode personnalisée pour la recherche et le tri
 */
public function findBySearchAndSort(?string $search, string $sort): array
{
    $qb = $this->createQueryBuilder('p');

    // 1. Gestion de la recherche (Filtre)
    if ($search) {
        $qb->andWhere('p.titre LIKE :q OR p.description LIKE :q OR p.categorie LIKE :q')
           ->setParameter('q', '%' . $search . '%');
    }

    // 2. Sécurité pour le tri (évite les erreurs de champ inexistant)
    // On vérifie que le champ demandé existe dans l'entité Produit
    $allowedSorts = ['idPR', 'titre', 'prix', 'stock', 'categorie'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'idPR'; // Tri par défaut si le champ est invalide
    }

    // 3. Application du tri
    $qb->orderBy('p.' . $sort, 'ASC');

    return $qb->getQuery()->getResult();
}
}