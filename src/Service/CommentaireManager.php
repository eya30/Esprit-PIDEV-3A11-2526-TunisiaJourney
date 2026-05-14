<?php
// src/Service/CommentaireManager.php
namespace App\Service;

use App\Entity\Commentaire;

class CommentaireManager
{
    public function validate(Commentaire $commentaire): bool
    {
        // Règle 1 : description obligatoire
        $desc = $commentaire->getDescription();
        if (empty($desc)) {
            throw new \InvalidArgumentException('La description du commentaire est obligatoire.');
        }

        // Règle 2 : description max 5000 caractères
        if (mb_strlen($desc) > 5000) {
            throw new \InvalidArgumentException(
                'La description ne peut pas dépasser 5000 caractères.'
            );
        }

        // Règle 3 : image — extension valide si renseignée
        $image = $commentaire->getImage();
        if ($image !== null && $image !== '') {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                throw new \InvalidArgumentException(
                    'L\'image doit être au format jpg, jpeg, png, gif ou webp.'
                );
            }
        }

        // Règle 4 : adresse IP valide si renseignée
        $ip = $commentaire->getIpAddress();
        if ($ip !== null && $ip !== '' && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('L\'adresse IP est invalide.');
        }

        // Règle 5 : user agent max 500 caractères
        $ua = $commentaire->getUserAgent();
        if ($ua !== null && mb_strlen($ua) > 500) {
            throw new \InvalidArgumentException(
                'Le user agent ne peut pas dépasser 500 caractères.'
            );
        }

        // Règle 6 : cancelled_at renseigné seulement si is_cancelled = true
        if ($commentaire->getCancelledAt() !== null && !$commentaire->getIsCancelled()) {
            throw new \InvalidArgumentException(
                'La date d\'annulation ne peut être définie que si le commentaire est annulé.'
            );
        }

        return true;
    }
}