<?php
// src/Repository/AvisChambreRepository.php

namespace App\Repository;

use App\Entity\AvisChambre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvisChambre>
 */
class AvisChambreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisChambre::class);
    }

    // Ajoutez vos méthodes personnalisées ici si besoin
}