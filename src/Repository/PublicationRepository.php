<?php
namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * Fix N+1 : charge les commentaires en une seule requête JOIN
     * @return Publication[]
     */
    public function findAllWithCommentaires(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.commentaires', 'c')
            ->addSelect('c')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fix findAll sans LIMIT : utilisé dans le dashboard
     * @return Publication[]
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}