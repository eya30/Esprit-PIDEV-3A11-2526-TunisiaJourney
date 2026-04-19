<?php

namespace App\EventListener;

use Doctrine\DBAL\Connection;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarListener implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection,
        private UrlGeneratorInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [CalendarEvents::SET_DATA => 'onCalendarSetData'];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $start = $calendar->getStart();
        $end   = $calendar->getEnd();

        $evenements = $this->connection->fetchAllAssociative(
            "SELECT IDEv, Titre, DateDebut, DateFin, Lieu FROM Evenement
              WHERE DateDebut <= ? AND (DateFin >= ? OR DateFin IS NULL)",
            [$end->format('Y-m-d'), $start->format('Y-m-d')]
        );

        foreach ($evenements as $ev) {
            $event = new Event(
                $ev['Titre'],
                new \DateTime($ev['DateDebut']),
                $ev['DateFin'] ? new \DateTime($ev['DateFin']) : null
            );
            $event->setOptions([
                'backgroundColor' => '#93032E',
                'borderColor'     => '#6B0221',
                'textColor'       => '#ffffff',
                'url'             => $this->router->generate(
                    'admin_evenement_edit', ['id' => $ev['IDEv']]
                ),
                'extendedProps'   => ['lieu' => $ev['Lieu'] ?? ''],
            ]);
            $calendar->addEvent($event);
        }
    }
}