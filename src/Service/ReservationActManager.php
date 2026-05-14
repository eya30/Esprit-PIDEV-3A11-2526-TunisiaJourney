<?php

namespace App\Service;

use App\Entity\ReservationAct;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ReservationActManager
{
    public function __construct(private ValidatorInterface $validator) {}

    public function validate(ReservationAct $reservation): ConstraintViolationListInterface
    {
        return $this->validator->validate($reservation);
    }

    public function isValid(ReservationAct $reservation): bool
    {
        return count($this->validate($reservation)) === 0;
    }
}