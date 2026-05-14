<?php

namespace App\Service;

use App\Entity\Activite;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ActiviteManager
{
    public function __construct(private ValidatorInterface $validator) {}

    public function validate(Activite $activite): ConstraintViolationListInterface
    {
        return $this->validator->validate($activite);
    }

    public function isValid(Activite $activite): bool
    {
        return count($this->validate($activite)) === 0;
    }
}