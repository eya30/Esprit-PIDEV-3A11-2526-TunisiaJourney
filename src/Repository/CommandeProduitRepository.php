<?php

namespace App\Repository;

use App\Entity\CommandeProduit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeProduit>
 */
class CommandeProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeProduit::class);
    }

    /**
     * @return CommandeProduit[]
     */
    public function findPanierByUser(User $user): array
    {
        return $this->createQueryBuilder('cp')
            ->where('cp.user = :user')
            ->andWhere('cp.isPanier = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findPanierItem(User $user, int $produitId): ?CommandeProduit
    {
        return $this->createQueryBuilder('cp')
            ->join('cp.produit', 'p')
            ->where('cp.user = :user')
            ->andWhere('cp.isPanier = true')
            ->andWhere('p.idPR = :produitId')
            ->setParameter('user', $user)
            ->setParameter('produitId', $produitId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTotalPanier(User $user): float
    {
        $items = $this->findPanierByUser($user);
        return array_reduce($items, fn($carry, $item) => $carry + $item->getSousTotal(), 0.0);
    }

    public function clearPanier(User $user): void
    {
        $this->createQueryBuilder('cp')
            ->delete()
            ->where('cp.user = :user')
            ->andWhere('cp.isPanier = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    // ✅ $user now typed as User
    public function findPanierItemByProduitAndTaille(User $user, int $produitId, ?string $taille): ?CommandeProduit
    {
        $qb = $this->createQueryBuilder('cp')
            ->where('cp.user = :user')
            ->andWhere('cp.isPanier = true')
            ->andWhere('cp.produit = :produitId')
            ->setParameter('user', $user)
            ->setParameter('produitId', $produitId);

        if ($taille === null) {
            $qb->andWhere('cp.taille IS NULL');
        } else {
            $qb->andWhere('cp.taille = :taille')
               ->setParameter('taille', $taille);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
