<?php

namespace App\Repository;

use App\Entity\PanierItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
/**
 * @extends ServiceEntityRepository<PanierItem>
 */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
class PanierItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PanierItem::class);
    }

<<<<<<< HEAD
    /**
     * @return array<int, PanierItem>
     */
=======
    // Récupérer tous les articles du panier d'un user
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

<<<<<<< HEAD
=======
    // Trouver un article spécifique dans le panier d'un user
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
=======
    // Calculer le total du panier d'un user
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function getTotalByUser(User $user): float
    {
        $items = $this->findByUser($user);
        return array_reduce($items, fn($carry, $item) => $carry + $item->getSousTotal(), 0.0);
    }

<<<<<<< HEAD
=======
    // Vider tout le panier d'un user
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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