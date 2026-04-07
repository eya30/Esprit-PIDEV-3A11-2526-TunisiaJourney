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
        // ========== STATISTIQUES GÉNÉRALES ==========
        $voyagesCount = $connection->fetchOne("SELECT COUNT(*) FROM voyages");
        $programmesCount = $connection->fetchOne("SELECT COUNT(*) FROM programmes");
        $reservationsCount = $connection->fetchOne("SELECT COUNT(*) FROM reservationprog");
        $usersCount = $connection->fetchOne("SELECT COUNT(*) FROM utilisateur");
        
        // ========== 1. RÉPARTITION PAR RÔLE (Pie Chart) ==========
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
        
        // ========== 2. PROGRAMMES LES PLUS RÉSERVÉS (Bar Chart) ==========
        $programmesPopulaires = $connection->fetchAllAssociative("
            SELECT 
                p.nom as programme_nom,
                COUNT(r.idRP) as total_reservations
            FROM programmes p
            LEFT JOIN reservationprog r ON r.idP = p.idProg
            GROUP BY p.idProg
            ORDER BY total_reservations DESC
            LIMIT 5
        ");
        
        // ========== 3. ÉVOLUTION DES RÉSERVATIONS (7 derniers jours) ==========
        $reservationsWeek = [];
        $joursLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $jourLabel = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'][date('w', strtotime("-$i days"))];
            $joursLabels[] = $jourLabel;
            
            $count = $connection->fetchOne("
                SELECT COUNT(*) FROM reservationprog 
                WHERE DATE(dateProgramme) = :date
            ", ['date' => $date]);
            
            $reservationsWeek[] = $count ?: 0;
        }
        
        // ========== DERNIÈRES DONNÉES ==========
        $derniersVoyages = $connection->fetchAllAssociative("
            SELECT * FROM voyages ORDER BY idV DESC LIMIT 5
        ");
        
        $dernieresReservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom 
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            ORDER BY r.idRP DESC LIMIT 5
        ");
        
        $derniersUsers = $connection->fetchAllAssociative("
            SELECT * FROM utilisateur ORDER BY id DESC LIMIT 5
        ");
        
        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'voyages' => $voyagesCount,
                'programmes' => $programmesCount,
                'reservations' => $reservationsCount,
                'utilisateurs' => $usersCount,
            ],
            'roles_stats' => $rolesStats,
            'programmes_populaires' => $programmesPopulaires,
            'reservations_week' => $reservationsWeek,
            'jours_labels' => $joursLabels,
            'derniers_voyages' => $derniersVoyages,
            'dernieres_reservations' => $dernieresReservations,
            'derniers_users' => $derniersUsers,
        ]);
    }
}