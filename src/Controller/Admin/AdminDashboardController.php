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
        // Statistiques pour les hôtels
        $hotelsCount = $connection->fetchOne("SELECT COUNT(*) FROM hotel");
        $chambresCount = $connection->fetchOne("SELECT COUNT(*) FROM chambre");
        $reservationsCount = $connection->fetchOne("SELECT COUNT(*) FROM reservation_chambre");
        
        // Nombre d'utilisateurs uniques dans les réservations
        $usersCount = $connection->fetchOne("SELECT COUNT(DISTINCT idUtilisateur) FROM reservation_chambre WHERE idUtilisateur IS NOT NULL");
        
        $stats = [
            'hotels' => $hotelsCount,
            'chambres' => $chambresCount,
            'reservations' => $reservationsCount,
            'clients' => $usersCount,
        ];
        
        // CHAMBRES les plus réservées (pour le graphique en camembert)
        $chambresPopulaires = $connection->fetchAllAssociative("
            SELECT c.num as chambre_num, c.type, c.prix_nuit, h.nom as hotel_nom, COUNT(r.idRes) as total
            FROM chambre c
            LEFT JOIN hotel h ON h.idH = c.idH
            LEFT JOIN reservation_chambre r ON r.idCh = c.idCh
            GROUP BY c.idCh
            ORDER BY total DESC
            LIMIT 6
        ");
        
        // Préparer les données pour le pie chart
        $pieLabels = [];
        $pieData = [];
        
        // Couleurs sophistiquées
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
        
        foreach ($chambresPopulaires as $chambre) {
            if ($chambre['total'] > 0) {
                $pieLabels[] = 'Chambre n°' . $chambre['chambre_num'] . ' (' . $chambre['hotel_nom'] . ')';
                $pieData[] = $chambre['total'];
            }
        }
        
        // S'il n'y a pas de données, afficher des données fictives
        if (empty($pieData)) {
            $pieLabels = ['Aucune réservation'];
            $pieData = [1];
        }
        
        // Hôtels populaires (basé sur les réservations)
        $hotelsPopulaires = $connection->fetchAllAssociative("
            SELECT h.nom, h.ville, COUNT(r.idRes) as total
            FROM hotel h
            LEFT JOIN chambre c ON c.idH = h.idH
            LEFT JOIN reservation_chambre r ON r.idCh = c.idCh
            GROUP BY h.idH
            ORDER BY total DESC
            LIMIT 5
        ");
        
        // Calculer les pourcentages
        $max = 0;
        foreach ($hotelsPopulaires as $hotel) {
            if ($hotel['total'] > $max) $max = $hotel['total'];
        }
        
        $hotelsStats = [];
        foreach ($hotelsPopulaires as $hotel) {
            $hotelsStats[] = [
                'nom' => $hotel['nom'],
                'pourcentage' => $max > 0 ? round(($hotel['total'] / $max) * 100) : 0
            ];
        }
        
        // Derniers hôtels ajoutés
        $derniersHotels = $connection->fetchAllAssociative("SELECT * FROM hotel ORDER BY idH DESC LIMIT 5");
        
        // Dernières réservations
        $dernieresReservations = $connection->fetchAllAssociative("
            SELECT r.*, c.num as chambre_num, h.nom as hotel_nom 
            FROM reservation_chambre r 
            LEFT JOIN chambre c ON r.idCh = c.idCh
            LEFT JOIN hotel h ON c.idH = h.idH
            ORDER BY r.idRes DESC LIMIT 5
        ");
        
        // Données pour le graphique (réservations des 7 derniers jours)
        $reservationsWeek = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $connection->fetchOne("SELECT COUNT(*) FROM reservation_chambre WHERE DATE(dateDebut) = ?", [$date]);
            $reservationsWeek[] = $count ?: 0;
        }
        
        return $this->render('admin/admin_dashboard/index.html.twig', [
            'stats' => $stats,
            'hotels_populaires' => $hotelsStats,
            'derniers_hotels' => $derniersHotels,
            'dernieres_reservations' => $dernieresReservations,
            'stats_reservations_week' => json_encode($reservationsWeek),
            'pie_labels' => json_encode($pieLabels),
            'pie_data' => json_encode($pieData),
            'pie_colors' => json_encode($pieColors),
        ]);
    }
}