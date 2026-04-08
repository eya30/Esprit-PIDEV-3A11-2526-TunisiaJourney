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
        // ── KPI ──
        $totalUsers        = $connection->fetchOne("SELECT COUNT(*) FROM utilisateur");
        $totalEvenements   = $connection->fetchOne("SELECT COUNT(*) FROM Evenement");
        $totalReservations = $connection->fetchOne("SELECT COUNT(*) FROM reservationprog");
        $totalRevenue      = $connection->fetchOne("SELECT COALESCE(SUM(prixProg), 0) FROM reservationprog");

        // ── Graphique 1 : CA par programme (barres) ──
        $caParProgramme = $connection->fetchAllAssociative("
            SELECT p.nom as label, COALESCE(SUM(r.prixProg), 0) as total
            FROM programmes p
            LEFT JOIN reservationprog r ON r.idP = p.idProg
            GROUP BY p.idProg, p.nom
            ORDER BY total DESC
            LIMIT 7
        ");

        // ── Graphique 2 : Inscriptions par mois (courbe) ──
        $inscriptionsParMois = $connection->fetchAllAssociative("
            SELECT 
                DATE_FORMAT(date_inscription, '%b %Y') as mois,
                DATE_FORMAT(date_inscription, '%Y-%m') as mois_sort,
                COUNT(*) as count
            FROM utilisateur
            GROUP BY mois, mois_sort
            ORDER BY mois_sort ASC
            LIMIT 12
        ");

        // ── Graphique 3 : Top événements (pie avec %) ──
        $topEvenements = $connection->fetchAllAssociative("
            SELECT e.Titre as label, COUNT(r.IDRes) as count
            FROM Evenement e
            LEFT JOIN Activite a ON a.IDEv = e.IDEv
            LEFT JOIN ReservationAct r ON r.IDAct = a.IDAct
            GROUP BY e.IDEv, e.Titre
            HAVING count > 0
            ORDER BY count DESC
            LIMIT 6
        ");

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'users'        => $totalUsers,
                'evenements'   => $totalEvenements,
                'reservations' => $totalReservations,
                'revenue'      => number_format($totalRevenue, 0, ',', ' '),
            ],
            'ca_par_programme'      => $caParProgramme,
            'inscriptions_par_mois' => $inscriptionsParMois,
            'top_evenements'        => $topEvenements,
        ]);
    }
}