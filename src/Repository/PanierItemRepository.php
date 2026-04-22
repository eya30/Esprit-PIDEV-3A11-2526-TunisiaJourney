<?php

namespace App\Repository;

use App\Entity\PanierItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PanierItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PanierItem::class);
    }

    // Récupérer tous les articles du panier d'un user
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    // Trouver un article spécifique dans le panier d'un user
    public function findOneByUserAndProduit(User $user, int $produitId): ?PanierItem
    {
        return $this->createQueryBuilder('p')
            ->join('p.produit', 'pr')
            ->where('p.user = :user')
            ->andWhere('pr.idPR = :produitId')
            ->setParameter('user', $user)
            ->setParameter('produitId', $produitId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Calculer le total du panier d'un user
    public function getTotalByUser(User $user): float
    {
        $items = $this->findByUser($user);
        return array_reduce($items, fn($carry, $item) => $carry + $item->getSousTotal(), 0.0);
    }

    // Vider tout le panier d'un user
    public function clearByUser(User $user): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}