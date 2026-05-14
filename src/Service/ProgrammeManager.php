<?php

namespace App\Service;

use App\Entity\Programme;

class ProgrammeManager
{
    /**
     * Valide les règles métier de l'entité Programme.
     *
     * Règles :
     * 1. Le nom est obligatoire et ne doit pas contenir de chiffres
     * 2. La description est obligatoire
     * 3. La date de début est obligatoire
     * 4. La date de fin est obligatoire et doit être postérieure à la date de début
     * 5. Le lieu est obligatoire
     * 6. L'activité associée est obligatoire
     * 7. L'hôtel est obligatoire
     */
    public function validate(Programme $programme): bool
    {
        // Règle 1 : Nom obligatoire
        if (empty($programme->getNom())) {
            throw new \InvalidArgumentException('Le nom du programme est obligatoire.');
        }

        // Règle 1 : Nom sans chiffres
        if (preg_match('/[0-9]/', $programme->getNom())) {
            throw new \InvalidArgumentException('Le nom ne doit pas contenir de chiffres.');
        }

        // Règle 2 : Description obligatoire
        if (empty($programme->getDescription())) {
            throw new \InvalidArgumentException('La description est obligatoire.');
        }

        // Règle 3 : Date de début obligatoire
        if ($programme->getDateDebut() === null) {
            throw new \InvalidArgumentException('La date de début est obligatoire.');
        }

        // Règle 4 : Date de fin obligatoire
        if ($programme->getDateFin() === null) {
            throw new \InvalidArgumentException('La date de fin est obligatoire.');
        }

        // Règle 4 : Date de fin postérieure à la date de début
        if ($programme->getDateFin() <= $programme->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        // Règle 5 : Lieu obligatoire
        if (empty($programme->getLieu())) {
            throw new \InvalidArgumentException('Le lieu est obligatoire.');
        }

        // Règle 6 : Activité associée obligatoire
        if (empty($programme->getActiviteAssociee())) {
            throw new \InvalidArgumentException("L'activité associée est obligatoire.");
        }

        // Règle 7 : Hôtel obligatoire
        if (empty($programme->getHotel())) {
            throw new \InvalidArgumentException("L'hôtel est obligatoire.");
        }

        return true;
    }
}
