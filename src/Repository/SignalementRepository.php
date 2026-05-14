<?php
// src/Repository/SignalementRepository.php

namespace App\Repository;

use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signalement>
 */
class SignalementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signalement::class);
    }

    /**
     * Tous les signalements non traités, du plus récent au plus ancien.
     *
     * @return array<int, Signalement>
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isTreated = false')
            ->orderBy('s.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Signalements d'un contenu précis.
     *
     * @return array<int, Signalement>
     */
    public function findByTarget(string $type, int $targetId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.type = :type')
            ->andWhere('s.targetId = :targetId')
            ->setParameter('type', $type)
            ->setParameter('targetId', $targetId)
            ->orderBy('s.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}