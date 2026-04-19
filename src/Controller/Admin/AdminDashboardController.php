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
        // ========== KPI ==========
        $totalRevenue = $connection->fetchOne("SELECT COALESCE(SUM(prixProg), 0) FROM reservationprog WHERE statutPaiement = 'payé'");
        $totalReservations = $connection->fetchOne("SELECT COUNT(*) FROM reservationprog");
        $totalUsers = $connection->fetchOne("SELECT COUNT(*) FROM utilisateur");
        
        // Réservations par jour (moyenne des 30 derniers jours)
        $avgPerDay = $connection->fetchOne("
            SELECT COALESCE(ROUND(COUNT(*) / 30, 1), 0) 
            FROM reservationprog 
            WHERE dateProgramme >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // ========== GRAPHIQUE 1: TOP 5 DESTINATIONS ==========
        $topDestinations = $connection->fetchAllAssociative("
            SELECT 
                v.nom as destination,
                COUNT(r.idRP) as total_reservations
            FROM voyages v
            LEFT JOIN programmes p ON p.idV = v.idV
            LEFT JOIN reservationprog r ON r.idP = p.idProg
            GROUP BY v.idV
            ORDER BY total_reservations DESC
            LIMIT 5
        ");
        
        // ========== GRAPHIQUE 2: RÉPARTITION UTILISATEURS ==========
        $rolesStats = $connection->fetchAllAssociative("
            SELECT 
                CASE 
                    WHEN role = 'ROLE_ADMIN' THEN 'Administrateurs'
                    ELSE 'Membres'
                END as label,
                COUNT(*) as count 
            FROM utilisateur 
            GROUP BY label
        ");
        
        // ========== DERNIÈRES DONNÉES ==========
        $recentReservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom 
            FROM reservationprog r
            LEFT JOIN programmes p ON r.idP = p.idProg
            ORDER BY r.dateProgramme DESC
            LIMIT 5
        ");
        
        $recentUsers = $connection->fetchAllAssociative("
            SELECT * FROM utilisateur 
            ORDER BY date_inscription DESC 
            LIMIT 5
        ");
        
        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'revenue' => number_format($totalRevenue, 0, ',', ' '),
                'reservations' => $totalReservations,
                'users' => $totalUsers,
                'avg_per_day' => $avgPerDay,
            ],
            'top_destinations' => $topDestinations,
            'roles_stats' => $rolesStats,
            'recent_reservations' => $recentReservations,
            'recent_users' => $recentUsers,
        ]);
    }
}