<?php
// src/Service/ForumManager.php
namespace App\Service;

use App\Entity\Forum;

class ForumManager
{
    public function validate(Forum $forum): bool
    {
        $nom = $forum->getNom();

        // Règle 1 : nom obligatoire
        if (empty($nom)) {
            throw new \InvalidArgumentException('Le nom du forum est obligatoire.');
        }

        // Règle 2 : longueur min 3
        if (mb_strlen($nom) < 3) {
            throw new \InvalidArgumentException('Le nom doit contenir au moins 3 caractères.');
        }

        // Règle 3 : longueur max 100
        if (mb_strlen($nom) > 100) {
            throw new \InvalidArgumentException('Le nom ne peut pas dépasser 100 caractères.');
        }

        // Règle 4 : doit commencer par une lettre ou un chiffre
        if (!preg_match('/^[a-zA-ZÀ-ÿ0-9]/u', $nom)) {
            throw new \InvalidArgumentException(
                'Le nom du forum doit commencer par une lettre ou un chiffre.'
            );
        }

        // Règle 5 : pas de symboles spéciaux interdits
        if (!preg_match('/^[a-zA-ZÀ-ÿ0-9\s\'\"\-\_\.\,\?\!éèêëàâùûüîïôçœæÉÈÊËÀÂÙÛÜÎÏÔÇŒÆ]+$/u', $nom)) {
            throw new \InvalidArgumentException(
                'Le nom du forum ne doit pas contenir de symboles spéciaux (@, +, *, #, &…).'
            );
        }

        // Règle 6 : thème obligatoire
        $theme = $forum->getTheme();
        if (empty($theme)) {
            throw new \InvalidArgumentException('Veuillez sélectionner un thème.');
        }

        // Règle 7 : status valide
        $status = $forum->getStatus();
        if (!in_array($status, ['actif', 'inactif'], true)) {
            throw new \InvalidArgumentException('Le statut doit être "actif" ou "inactif".');
        }

        return true;
    }
}