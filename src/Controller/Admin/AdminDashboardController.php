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
        $voyagesCount = $connection->fetchOne("SELECT COUNT(*) FROM voyages");
        $programmesCount = $connection->fetchOne("SELECT COUNT(*) FROM programmes");
        $reservationsCount = $connection->fetchOne("SELECT COUNT(*) FROM reservationprog");
        
        // Nombre d'utilisateurs uniques dans les réservations
        $usersCount = $connection->fetchOne("SELECT COUNT(DISTINCT user_id) FROM reservationprog WHERE user_id IS NOT NULL");
        
        $stats = [
            'voyages' => $voyagesCount,
            'programmes' => $programmesCount,
            'reservations' => $reservationsCount,
            'clients' => $usersCount,
        ];
        
        // PROGRAMMES les plus réservés (pour le graphique en camembert)
        $programmesPopulaires = $connection->fetchAllAssociative("
            SELECT p.nom as programme_nom, p.lieu, COUNT(r.idRP) as total
            FROM programmes p
            LEFT JOIN reservationprog r ON r.idP = p.idProg
            GROUP BY p.idProg
            ORDER BY total DESC
            LIMIT 6
        ");
        
        // Préparer les données pour le pie chart
        $pieLabels = [];
        $pieData = [];
        
        // Couleurs sophistiquées : bleu nuit, rouge pastel, vert militaire, jaune moutarde, etc.
        $pieColors = [
            '#1B2A4A', // Bleu nuit
            '#E8A3A3', // Rouge pastel
            '#5B6C3F', // Vert militaire
            '#D4A13E', // Jaune moutarde
            '#8B5E3C', // Marron élégant
            '#4A6B6B', // Vert sauge
            '#A8554E', // Terre cuite
            '#7C6E65', // Taupe
            '#2D6A4F', // Vert forêt
            '#9C6B3E'  // Ocre
        ];
        
        foreach ($programmesPopulaires as $programme) {
            if ($programme['total'] > 0) {
                $pieLabels[] = $programme['programme_nom'] . ' (' . $programme['lieu'] . ')';
                $pieData[] = $programme['total'];
            }
        }
        
        // S'il n'y a pas de données, afficher des données fictives
        if (empty($pieData)) {
            $pieLabels = ['Aucune réservation'];
            $pieData = [1];
        }
        
        // Destinations populaires (basé sur les voyages)
        $destinationsPopulaires = $connection->fetchAllAssociative("
            SELECT v.nom, COUNT(r.idRP) as total
            FROM voyages v
            LEFT JOIN programmes p ON p.idV = v.idV
            LEFT JOIN reservationprog r ON r.idP = p.idProg
            GROUP BY v.idV
            ORDER BY total DESC
            LIMIT 5
        ");
        
        // Calculer les pourcentages
        $max = 0;
        foreach ($destinationsPopulaires as $dest) {
            if ($dest['total'] > $max) $max = $dest['total'];
        }
        
        $destinations = [];
        foreach ($destinationsPopulaires as $dest) {
            $destinations[] = [
                'nom' => $dest['nom'],
                'pourcentage' => $max > 0 ? round(($dest['total'] / $max) * 100) : 0
            ];
        }
        
        // Derniers voyages
        $derniersVoyages = $connection->fetchAllAssociative("SELECT * FROM voyages ORDER BY idV DESC LIMIT 5");
        
        // Dernières réservations
        $dernieresReservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom 
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            ORDER BY r.idRP DESC LIMIT 5
        ");
        
        // Données pour le graphique (réservations des 7 derniers jours)
        $reservationsWeek = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $connection->fetchOne("SELECT COUNT(*) FROM reservationprog WHERE DATE(dateProgramme) = ?", [$date]);
            $reservationsWeek[] = $count ?: 0;
        }
        
        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'destinations_populaires' => $destinations,
            'derniers_voyages' => $derniersVoyages,
            'dernieres_reservations' => $dernieresReservations,
            'stats_reservations_week' => json_encode($reservationsWeek),
            'pie_labels' => json_encode($pieLabels),
            'pie_data' => json_encode($pieData),
            'pie_colors' => json_encode($pieColors),
        ]);
    }
}