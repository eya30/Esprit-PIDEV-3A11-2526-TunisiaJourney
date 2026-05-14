<?php

namespace App\Service;

use App\Entity\Hotel;

class HotelManager
{
    public function validate(Hotel $hotel): bool
    {
        // --- nom ---
        if (empty($hotel->getNom())) {
            throw new \InvalidArgumentException("Le nom de l'hôtel est obligatoire.");
        }
        $lenNom = strlen($hotel->getNom());
        if ($lenNom < 2 || $lenNom > 120) {
            throw new \InvalidArgumentException("Le nom doit contenir entre 2 et 120 caractères.");
        }
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\'-]+$/u", $hotel->getNom())) {
            throw new \InvalidArgumentException("Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");
        }

        // --- ville ---
        if (empty($hotel->getVille())) {
            throw new \InvalidArgumentException("La ville est obligatoire.");
        }
        $lenVille = strlen($hotel->getVille());
        if ($lenVille < 2 || $lenVille > 120) {
            throw new \InvalidArgumentException("La ville doit contenir entre 2 et 120 caractères.");
        }
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\'-]+$/u", $hotel->getVille())) {
            throw new \InvalidArgumentException("La ville ne doit contenir que des lettres, espaces, apostrophes ou tirets.");
        }

        // --- adresse (nullable, max 255) ---
        if ($hotel->getAdresse() !== null && strlen($hotel->getAdresse()) > 255) {
            throw new \InvalidArgumentException("L'adresse ne peut pas dépasser 255 caractères.");
        }

        // --- etoiles ---
        if ($hotel->getEtoiles() === null) {
            throw new \InvalidArgumentException("Le nombre d'étoiles est obligatoire.");
        }
        if ($hotel->getEtoiles() < 1 || $hotel->getEtoiles() > 5) {
            throw new \InvalidArgumentException("Les étoiles doivent être comprises entre 1 et 5.");
        }

        // --- description ---
        if (empty($hotel->getDescription())) {
            throw new \InvalidArgumentException("La description est obligatoire.");
        }
        $lenDesc = strlen($hotel->getDescription());
        if ($lenDesc < 10) {
            throw new \InvalidArgumentException("La description doit contenir au moins 10 caractères.");
        }
        if ($lenDesc > 5000) {
            throw new \InvalidArgumentException("La description ne peut pas dépasser 5000 caractères.");
        }

        // --- promotion (nullable, 0-100) ---
        if ($hotel->getPromotion() !== null) {
            if ($hotel->getPromotion() < 0) {
                throw new \InvalidArgumentException("La promotion ne peut pas être négative.");
            }
            if ($hotel->getPromotion() > 100) {
                throw new \InvalidArgumentException("La promotion doit être comprise entre 0 et 100%.");
            }
        }

        // --- status (nullable) ---
        $statusValides = ['disponible', 'indisponible', 'maintenance'];
        if ($hotel->getStatus() !== null && !in_array($hotel->getStatus(), $statusValides)) {
            throw new \InvalidArgumentException("Le status doit être : disponible, indisponible ou maintenance.");
        }

        // --- idUtilisateur ---
        if (empty($hotel->getIdUtilisateur()) || $hotel->getIdUtilisateur() <= 0) {
            throw new \InvalidArgumentException("L'ID utilisateur est obligatoire et doit être positif.");
        }

        return true;
    }
}
