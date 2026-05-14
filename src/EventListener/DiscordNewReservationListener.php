<?php

namespace App\EventListener;

use App\Service\DiscordNotifierService;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DiscordNewReservationListener implements EventSubscriberInterface
{
    private Connection $connection;
    private DiscordNotifierService $discordNotifier;
    private ?int $lastCheckedId = null;

    public function __construct(
        Connection $connection,
        DiscordNotifierService $discordNotifier
    ) {
        $this->connection      = $connection;
        $this->discordNotifier = $discordNotifier;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        $session             = $request->getSession();
        $this->lastCheckedId = $session->get('last_notified_reservation_id', 0);

        $this->checkNewReservations($session);
    }

    private function checkNewReservations(SessionInterface $session): void
    {
        try {
            $row = $this->connection->fetchAssociative("
                SELECT r.idRP,
                       r.nom,
                       r.prenom,
                       r.telephone,
                       r.email,
                       r.nbre,
                       r.prixProg,
                       r.dateProgramme,
                       p.nom  AS programme_nom,
                       v.nom  AS voyage_nom,
                       v.idV  AS voyage_id
                FROM reservationprog r
                LEFT JOIN programmes p ON r.idP  = p.idProg
                LEFT JOIN voyages    v ON p.idV   = v.idV
                WHERE r.idRP > ?
                ORDER BY r.idRP DESC
                LIMIT 1
            ", [$this->lastCheckedId]);

            // FIX PHPStan :65 — on construit un tableau typé conforme au
            // shape attendu par notifyNewReservation() au lieu de passer
            // directement le résultat brut (non-empty-array<string, mixed>).
            if ($row === false || (int)$row['idRP'] <= (int)$this->lastCheckedId) {
                return;
            }

            /** @var array{
             *     idRP: int,
             *     nom: string,
             *     prenom: string,
             *     telephone: string,
             *     email: string,
             *     nbre: int,
             *     prixProg?: float,
             *     dateProgramme: string,
             *     programme_nom?: string,
             *     voyage_nom?: string,
             *     voyage_id?: int
             * } $reservation
             */
            $reservation = [
                'idRP'          => (int)$row['idRP'],
                'nom'           => (string)$row['nom'],
                'prenom'        => (string)$row['prenom'],
                'telephone'     => (string)$row['telephone'],
                'email'         => (string)$row['email'],
                'nbre'          => (int)$row['nbre'],
                'dateProgramme' => (string)$row['dateProgramme'],
            ];

            if (isset($row['prixProg'])) {
                $reservation['prixProg'] = (float)$row['prixProg'];
            }
            if (isset($row['programme_nom'])) {
                $reservation['programme_nom'] = (string)$row['programme_nom'];
            }
            if (isset($row['voyage_nom'])) {
                $reservation['voyage_nom'] = (string)$row['voyage_nom'];
            }
            if (isset($row['voyage_id'])) {
                $reservation['voyage_id'] = (int)$row['voyage_id'];
            }

            $sent = $this->discordNotifier->notifyNewReservation($reservation);

            if ($sent) {
                $this->lastCheckedId = $reservation['idRP'];
                $session->set('last_notified_reservation_id', $this->lastCheckedId);

                $notifications = $session->get('discord_notifications', []);
                array_unshift($notifications, [
                    'id'      => $reservation['idRP'],
                    'message' => "🆕 Nouvelle réservation #{$reservation['idRP']} - {$reservation['prenom']} {$reservation['nom']}",
                    'time'    => date('H:i:s'),
                    'read'    => false,
                ]);

                $session->set('discord_notifications', array_slice($notifications, 0, 20));
            }

        } catch (\Exception) {
            // Erreur silencieuse
        }
    }
}