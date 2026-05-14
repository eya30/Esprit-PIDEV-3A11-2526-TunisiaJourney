<?php
namespace App\Repository;

use App\Entity\Forum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Forum>
 */
class ForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forum::class);
    }

    /**
     * Fix N+1 : charge les publications en une seule requête JOIN
     * @return Forum[]
     */
    public function findAllWithPublications(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.publications', 'p')
            ->addSelect('p')
            ->orderBy('f.idF', 'ASC')
            ->getQuery()
            ->getResult();
    }
}