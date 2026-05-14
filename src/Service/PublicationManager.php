<?php
// src/Service/PublicationManager.php
namespace App\Service;

use App\Entity\Publication;

class PublicationManager
{
    public function validate(Publication $publication): bool
    {
        // Règle 1 : description max 200 caractères
        $desc = $publication->getDescription();
        if ($desc !== null && mb_strlen($desc) > 200) {
            throw new \InvalidArgumentException(
                'La description ne peut pas dépasser 200 caractères.'
            );
        }

        // Règle 2 : id utilisateur obligatoire
        if ($publication->getId() === null) {
            throw new \InvalidArgumentException("L'identifiant utilisateur est obligatoire.");
        }

        // Règle 3 : vues ne peut pas être négative
        if ($publication->getVues() < 0) {
            throw new \InvalidArgumentException('Le nombre de vues ne peut pas être négatif.');
        }

        // Règle 4 : si vidéo YouTube, l'ID doit être extractible (11 caractères)
        if ($publication->isYoutubeVideo()) {
            $ytId = $publication->getYoutubeId();
            if ($ytId === null || mb_strlen($ytId) !== 11) {
                throw new \InvalidArgumentException('URL YouTube invalide.');
            }
        }

        return true;
    }
}