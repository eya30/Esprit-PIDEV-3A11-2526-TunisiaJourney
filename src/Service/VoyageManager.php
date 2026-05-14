<?php

namespace App\Service;

use App\Entity\Voyage;

class VoyageManager
{
    /**
     * Valide les règles métier de l'entité Voyage.
     *
     * Règles :
     * 1. Le nom est obligatoire et ne doit pas contenir de chiffres
     * 2. La description est obligatoire
     * 3. La capacité doit être un entier positif et <= 200
     * 4. Le prix doit être supérieur à 0
     * 5. La date de création est obligatoire
     * 6. L'heure est obligatoire
     */
    public function validate(Voyage $voyage): bool
    {
        // Règle 1 : Nom obligatoire
        if (empty($voyage->getNom())) {
            throw new \InvalidArgumentException('Le nom du voyage est obligatoire.');
        }

        // Règle 1 : Nom sans chiffres
        if (preg_match('/[0-9]/', $voyage->getNom())) {
            throw new \InvalidArgumentException('Le nom du voyage ne doit pas contenir de chiffres.');
        }

        // Règle 2 : Description obligatoire
        if (empty($voyage->getDescription())) {
            throw new \InvalidArgumentException('La description est obligatoire.');
        }

        // Règle 3 : Capacité obligatoire et positive
        if ($voyage->getCapacite() === null) {
            throw new \InvalidArgumentException('La capacité est obligatoire.');
        }

        if ($voyage->getCapacite() <= 0) {
            throw new \InvalidArgumentException('La capacité doit être un nombre positif.');
        }

        if ($voyage->getCapacite() > 200) {
            throw new \InvalidArgumentException('La capacité ne peut pas dépasser 200 personnes.');
        }

        // Règle 4 : Prix obligatoire et positif
        if ($voyage->getPrix() === null) {
            throw new \InvalidArgumentException('Le prix est obligatoire.');
        }

        if ($voyage->getPrix() <= 0) {
            throw new \InvalidArgumentException('Le prix doit être supérieur à 0.');
        }

        // Règle 5 : Date de création obligatoire
        if ($voyage->getDateCreation() === null) {
            throw new \InvalidArgumentException('La date de création est obligatoire.');
        }

        // Règle 6 : Heure obligatoire
        if ($voyage->getHeure() === null) {
            throw new \InvalidArgumentException("L'heure est obligatoire.");
        }

        return true;
    }
}
