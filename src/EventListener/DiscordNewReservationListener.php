<?php

namespace App\EventListener;

use App\Service\DiscordNotifierService;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DiscordNewReservationListener implements EventSubscriberInterface
{
    private $connection;
    private $discordNotifier;
    private $lastCheckedId = null;

    public function __construct(
        Connection $connection,
        DiscordNotifierService $discordNotifier
    ) {
        $this->connection = $connection;
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
        
        // Vérifier si on est dans le backoffice
        if (strpos($request->getPathInfo(), '/admin') !== 0) {
            return;
        }

        // Récupérer l'ID de la dernière réservation notifiée
        $session = $request->getSession();
        $this->lastCheckedId = $session->get('last_notified_reservation_id', 0);

        // Vérifier les nouvelles réservations
        $this->checkNewReservations($session);
    }

    private function checkNewReservations($session): void
    {
        try {
            // Récupérer la dernière réservation
            $newReservation = $this->connection->fetchAssociative("
                SELECT r.*, p.nom as programme_nom, v.nom as voyage_nom, v.idV as voyage_id
                FROM reservationprog r 
                LEFT JOIN programmes p ON r.idP = p.idProg 
                LEFT JOIN voyages v ON p.idV = v.idV
                WHERE r.idRP > ?
                ORDER BY r.idRP DESC
                LIMIT 1
            ", [$this->lastCheckedId]);

            if ($newReservation && $newReservation['idRP'] > $this->lastCheckedId) {
                // Envoyer la notification Discord
                $sent = $this->discordNotifier->notifyNewReservation($newReservation);
                
                if ($sent) {
                    $this->lastCheckedId = $newReservation['idRP'];
                    $session->set('last_notified_reservation_id', $this->lastCheckedId);
                    
                    // Stocker en session pour afficher dans le backoffice
                    $notifications = $session->get('discord_notifications', []);
                    array_unshift($notifications, [
                        'id' => $newReservation['idRP'],
                        'message' => "🆕 Nouvelle réservation #{$newReservation['idRP']} - {$newReservation['prenom']} {$newReservation['nom']}",
                        'time' => date('H:i:s'),
                        'read' => false
                    ]);
                    
                    $notifications = array_slice($notifications, 0, 20);
                    $session->set('discord_notifications', $notifications);
                }
            }
        } catch (\Exception $e) {
            // Log erreur silencieuse
        }
    }
}