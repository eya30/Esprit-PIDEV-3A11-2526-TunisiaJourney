<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(Connection $connection): Response
    {
        // Statistiques
        $evenementsCount = $connection->fetchOne("SELECT COUNT(*) FROM Evenement");
        $activitesCount = $connection->fetchOne("SELECT COUNT(*) FROM Activite");
        $reservationsCount = $connection->fetchOne("SELECT COUNT(*) FROM ReservationAct");

        // Nombre d'utilisateurs uniques dans les réservations seulement 
        $usersCount = $connection->fetchOne("SELECT COUNT(DISTINCT id) FROM ReservationAct WHERE id IS NOT NULL");

        $stats = [
            'evenements'   => $evenementsCount,
            'activites'    => $activitesCount,
            'reservations' => $reservationsCount,
            'clients'      => $usersCount,
        ];

        // Événements populaires (basé sur les réservations via activité)
        $evenementsPopulaires = $connection->fetchAllAssociative("
            SELECT e.Titre, COUNT(r.IDRes) as total
            FROM Evenement e
            LEFT JOIN Activite a ON a.IDEv = e.IDEv
            LEFT JOIN ReservationAct r ON r.IDAct = a.IDAct
            GROUP BY e.IDEv
            ORDER BY total DESC
            LIMIT 5
        ");

        // Calculer les pourcentages de chacune 
        $max = 0;
        foreach ($evenementsPopulaires as $ev) {
            if ($ev['total'] > $max) $max = $ev['total'];
        }

        $destinations = [];
        foreach ($evenementsPopulaires as $ev) {
            $destinations[] = [
                'nom'         => $ev['Titre'],
                'pourcentage' => $max > 0 ? round(($ev['total'] / $max) * 100) : 0
            ];
        }

        // Derniers événements
        $derniersEvenements = $connection->fetchAllAssociative(
            "SELECT * FROM Evenement ORDER BY IDEv DESC LIMIT 5"
        );

        // Dernières réservations
        $dernieresReservations = $connection->fetchAllAssociative("
            SELECT r.*, a.Titre as activite_titre 
            FROM ReservationAct r 
            LEFT JOIN Activite a ON r.IDAct = a.IDAct 
            ORDER BY r.IDRes DESC LIMIT 5
        ");

        // Données pour le graphique (réservations des 7 derniers jours)
        $reservationsWeek = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $connection->fetchOne(
                "SELECT COUNT(*) FROM ReservationAct WHERE DATE(DateReservation) = ?", [$date]
            );
            $reservationsWeek[] = $count ?: 0;
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'stats'                    => $stats,
            'destinations_populaires'  => $destinations,
            'derniers_evenements'      => $derniersEvenements,
            'dernieres_reservations'   => $dernieresReservations,
            'stats_reservations_week'  => json_encode($reservationsWeek),
        ]);
    }
}