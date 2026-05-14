<?php

namespace App\Service;

use App\Entity\Evenement;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class EvenementManager
{
    public function __construct(private ValidatorInterface $validator) {}

    public function validate(Evenement $evenement): ConstraintViolationListInterface
    {
        return $this->validator->validate($evenement);
    }

    public function isValid(Evenement $evenement): bool
    {
        return count($this->validate($evenement)) === 0;
    }
}