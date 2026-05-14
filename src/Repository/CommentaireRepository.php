<?php
namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * Fix N+1 : charge les commentaires avec leur publication en JOIN
     * @return Commentaire[]
     */
    public function findAllWithPublication(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.publication', 'p')
            ->addSelect('p')
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fix findAll sans LIMIT
     * @return Commentaire[]
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}