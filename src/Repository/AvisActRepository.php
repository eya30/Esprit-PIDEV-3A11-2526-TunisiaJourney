<?php

namespace App\Repository;

use App\Entity\AvisAct;
use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvisActRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisAct::class);
    }

    /**
     * Récupère tous les avis d'une activité (par objet Activite)
     * du plus récent au plus ancien
     */
    public function findByActivite(Activite $activite): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.activite = :activite')
            ->setParameter('activite', $activite)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les avis d'une activité (par ID)
     * du plus récent au plus ancien
     */
    public function findByActiviteId(int $activiteId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.activite = :activiteId')
            ->setParameter('activiteId', $activiteId)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la note moyenne d'une activité (par objet Activite)
     */
    public function getMoyenneNote(Activite $activite): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as moyenne')
            ->where('a.activite = :activite')
            ->setParameter('activite', $activite)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float)$result, 1) : 0;
    }

    /**
     * Calcule la note moyenne d'une activité (par ID)
     */
    public function getMoyenneNoteByActiviteId(int $activiteId): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as moyenne')
            ->where('a.activite = :activiteId')
            ->setParameter('activiteId', $activiteId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float)$result, 1) : 0;
    }

    /**
     * Récupère les 5 derniers avis d'une activité
     */
    public function findLastAvisByActivite(Activite $activite, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.activite = :activite')
            ->setParameter('activite', $activite)
            ->orderBy('a.dateAvis', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'avis pour une activité
     */
    public function countByActivite(Activite $activite): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.idAv)')
            ->where('a.activite = :activite')
            ->setParameter('activite', $activite)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère la répartition des notes pour une activité
     * Retourne un tableau [1=>nb, 2=>nb, 3=>nb, 4=>nb, 5=>nb]
     */
    public function getNoteRepartition(Activite $activite): array
    {
        $results = $this->createQueryBuilder('a')
            ->select('a.note, COUNT(a.idAv) as count')
            ->where('a.activite = :activite')
            ->setParameter('activite', $activite)
            ->groupBy('a.note')
            ->getQuery()
            ->getResult();

        $repartition = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($results as $result) {
            $repartition[$result['note']] = $result['count'];
        }
        return $repartition;
    }

    /**
     * Récupère tous les avis avec leurs commentaires (non vides)
     */
    public function findAvisWithCommentaire(Activite $activite): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.activite = :activite')
            ->andWhere('a.commentaire IS NOT NULL')
            ->andWhere('a.commentaire != :empty')
            ->setParameter('activite', $activite)
            ->setParameter('empty', '')
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }
}