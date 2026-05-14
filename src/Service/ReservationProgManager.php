<?php

namespace App\Service;

use App\Entity\ReservationProg;

class ReservationProgManager
{
    /**
     * Valide les règles métier de l'entité ReservationProg.
     *
     * Règles :
     * 1. Le nom est obligatoire, entre 2 et 50 caractères, lettres uniquement
     * 2. Le prénom est obligatoire, entre 2 et 50 caractères, lettres uniquement
     * 3. Le téléphone est obligatoire et doit contenir exactement 8 chiffres
     * 4. Le nombre de personnes doit être entre 1 et 20
     * 5. L'email doit être valide
     * 6. L'identifiant du programme est obligatoire
     */
    public function validate(ReservationProg $reservation): bool
    {
        // Règle 1 : Nom obligatoire
        if (empty($reservation->getNom())) {
            throw new \InvalidArgumentException('Le nom est obligatoire.');
        }

        if (strlen($reservation->getNom()) < 2) {
            throw new \InvalidArgumentException('Le nom doit contenir au moins 2 caractères.');
        }

        if (strlen($reservation->getNom()) > 50) {
            throw new \InvalidArgumentException('Le nom ne peut pas dépasser 50 caractères.');
        }

        if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/', $reservation->getNom())) {
            throw new \InvalidArgumentException('Le nom ne doit contenir que des lettres, espaces ou tirets.');
        }

        // Règle 2 : Prénom obligatoire
        if (empty($reservation->getPrenom())) {
            throw new \InvalidArgumentException('Le prénom est obligatoire.');
        }

        if (strlen($reservation->getPrenom()) < 2) {
            throw new \InvalidArgumentException('Le prénom doit contenir au moins 2 caractères.');
        }

        if (strlen($reservation->getPrenom()) > 50) {
            throw new \InvalidArgumentException('Le prénom ne peut pas dépasser 50 caractères.');
        }

        if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/', $reservation->getPrenom())) {
            throw new \InvalidArgumentException('Le prénom ne doit contenir que des lettres, espaces ou tirets.');
        }

        // Règle 3 : Téléphone — exactement 8 chiffres
        if (empty($reservation->getTelephone())) {
            throw new \InvalidArgumentException('Le téléphone est obligatoire.');
        }

        if (!preg_match('/^[0-9]{8}$/', $reservation->getTelephone())) {
            throw new \InvalidArgumentException('Le téléphone doit contenir exactement 8 chiffres.');
        }

        // Règle 4 : Nombre de personnes entre 1 et 20
        if ($reservation->getNbre() === null) {
            throw new \InvalidArgumentException('Le nombre de personnes est obligatoire.');
        }

        if ($reservation->getNbre() <= 0) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être supérieur à 0.');
        }

        if ($reservation->getNbre() > 20) {
            throw new \InvalidArgumentException('Le nombre de personnes ne peut pas dépasser 20.');
        }

        // Règle 5 : Email valide
        if (empty($reservation->getEmail())) {
            throw new \InvalidArgumentException("L'email est obligatoire.");
        }

        if (!filter_var($reservation->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Veuillez saisir un email valide.');
        }

        // Règle 6 : Programme obligatoire
        if (empty($reservation->getIdP())) {
            throw new \InvalidArgumentException('Le programme est obligatoire.');
        }

        return true;
    }
}
