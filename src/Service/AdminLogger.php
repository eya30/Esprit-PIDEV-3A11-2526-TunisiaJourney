<?php

namespace App\Service;

use App\Entity\AdminLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service injectable dans tous les controllers.
 * Usage : $this->logger->log($user, AdminLog::ACTION_EDIT_USER, 'eyaa bogh (id=19)', 'nom: bogh→smith');
 */
class AdminLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack           $requestStack,
    ) {}

    public function log(
        ?User   $acteur,
        string  $action,
        ?string $cible   = null,
        ?string $details = null
    ): void {
        $log = new AdminLog();
        $log->setActeur($acteur);
        $log->setAction($action);
        $log->setCible($cible);
        $log->setDetails($details ?: null);

        // Nom figé au moment de l'action
        if ($acteur) {
            $log->setActeurNom($acteur->getPrenom() . ' ' . $acteur->getNom());
            $roles = $acteur->getRoles();
            if (in_array('ROLE_SUPER_ADMIN', $roles)) {
                $log->setActeurRole('SUPER_ADMIN');
            } elseif (in_array('ROLE_ADMIN', $roles)) {
                $log->setActeurRole('ADMIN');
            } else {
                $log->setActeurRole('MEMBRE');
            }
        } else {
            $log->setActeurNom('Inconnu');
            $log->setActeurRole('—');
        }

        // IP
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIp($request->getClientIp() ?? '—');
        }

        $this->em->persist($log);
        $this->em->flush();
    }

    /**
     * Helper : compare deux valeurs et retourne "avant→après" si différentes
     */
    public static function diff(?string $label, mixed $before, mixed $after): ?string
    {
        $b = (string)($before ?? '');
        $a = (string)($after  ?? '');
        if ($b === $a) return null;
        return $label . ': ' . ($b ?: '—') . ' → ' . ($a ?: '—');
    }

    /**
     * Helper : construit la chaîne de détails à partir d'un tableau de diff
<<<<<<< HEAD
     *
     *  @param array<int, string|null> $diffs
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    public static function buildDetails(array $diffs): ?string
    {
        $parts = array_filter($diffs);
        return empty($parts) ? null : implode(' | ', $parts);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
