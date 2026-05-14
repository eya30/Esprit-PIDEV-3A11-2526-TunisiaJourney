<?php

namespace App\Repository;

use App\Entity\AdminLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
/**
 * @extends ServiceEntityRepository<AdminLog>
 */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
class AdminLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminLog::class);
    }

    /**
     * Logs paginés avec filtres optionnels
<<<<<<< HEAD
     *
     * @return array<string, mixed>
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    public function findFiltered(
        ?string $action = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.acteur', 'u')
            ->orderBy('l.createdAt', 'DESC');

        if ($action) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $action);
        }

        if ($search) {
            $qb->andWhere('l.acteurNom LIKE :search OR l.cible LIKE :search OR l.details LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom) {
            $qb->andWhere('l.createdAt >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($dateFrom . ' 00:00:00'));
        }

        if ($dateTo) {
            $qb->andWhere('l.createdAt <= :dateTo')
               ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

<<<<<<< HEAD
        // ligne 61 : getSingleScalarResult() retourne mixed → cast int
        $totalRaw = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();
        $total    = (int) $totalRaw;
=======
        $total = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

        $logs = $qb->setFirstResult(($page - 1) * $limit)
                   ->setMaxResults($limit)
                   ->getQuery()
                   ->getResult();

        return [
<<<<<<< HEAD
            'logs'    => $logs,
            'total'   => $total,
            'pages'   => (int) ceil($total / $limit),
            'current' => $page,
            'limit'   => $limit,
=======
            'logs'       => $logs,
            'total'      => (int) $total,
            'pages'      => (int) ceil($total / $limit),
            'current'    => $page,
            'limit'      => $limit,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        ];
    }

    /**
     * Les N derniers logs (pour widget dashboard)
<<<<<<< HEAD
     *
     * @return array<int, AdminLog>
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Stats par action (pour graphique)
<<<<<<< HEAD
     *
     * @return array<int, array<string, mixed>>
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    public function countByAction(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.action, COUNT(l.id) as total')
            ->groupBy('l.action')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
