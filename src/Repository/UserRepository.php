<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
/**
 * @extends ServiceEntityRepository<User>
 */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
