<?php

namespace App\Service;

use App\Entity\Chambre;

class ChambreManager
{
    public function validate(Chambre $chambre): bool
    {
        // --- num ---
        if (empty($chambre->getNum()) || $chambre->getNum() <= 0) {
            throw new \InvalidArgumentException('Le numéro de chambre est obligatoire et doit être positif.');
        }

        // --- type ---
        $typesValides = ['simple', 'double', 'triple', 'suite', 'presidentielle', 'familiale'];
        if (empty($chambre->getType())) {
            throw new \InvalidArgumentException('Le type de chambre est obligatoire.');
        }
        if (!in_array($chambre->getType(), $typesValides)) {
            throw new \InvalidArgumentException('Le type de chambre doit être : simple, double, triple, suite, presidentielle ou familiale.');
        }

        // --- prix_nuit ---
        if ($chambre->getPrixNuit() === null || $chambre->getPrixNuit() <= 0) {
            throw new \InvalidArgumentException('Le prix par nuit est obligatoire et doit être positif.');
        }
        if ($chambre->getPrixNuit() < 10 || $chambre->getPrixNuit() > 2000) {
            throw new \InvalidArgumentException('Le prix par nuit doit être compris entre 10 et 2000 €.');
        }

        // --- status (nullable) ---
        $statusValides = ['disponible', 'indisponible', 'maintenance'];
        if ($chambre->getStatus() !== null && !in_array($chambre->getStatus(), $statusValides)) {
            throw new \InvalidArgumentException('Le status doit être : disponible, indisponible ou maintenance.');
        }

        // --- capacite_max ---
        if ($chambre->getCapaciteMax() === null) {
            throw new \InvalidArgumentException('La capacité maximale est obligatoire.');
        }
        if ($chambre->getCapaciteMax() < 1 || $chambre->getCapaciteMax() > 10) {
            throw new \InvalidArgumentException('La capacité maximale doit être comprise entre 1 et 10 personnes.');
        }

        // --- description (nullable) ---
        if ($chambre->getDescription() !== null) {
            $len = strlen($chambre->getDescription());
            if ($len < 10) {
                throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères.');
            }
            if ($len > 2000) {
                throw new \InvalidArgumentException('La description ne peut pas dépasser 2000 caractères.');
            }
        }

        // --- modele3D_URL (nullable) ---
        if ($chambre->getModele3DURL() !== null && !filter_var($chambre->getModele3DURL(), FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("L'URL du modèle 3D doit être une URL valide.");
        }

        // --- hotel (relation obligatoire) ---
        if ($chambre->getHotel() === null) {
            throw new \InvalidArgumentException("L'hôtel associé est obligatoire.");
        }

        return true;
    }
}
