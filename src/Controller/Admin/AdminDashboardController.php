<?php

namespace App\Controller\Admin;

use App\Repository\ForumRepository;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    // Route avec /admin/dashboard
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    // Route sans /admin (redirige vers la première)
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        Connection $connection,
        ForumRepository $forumRepo,
        PublicationRepository $publicationRepo,
        CommentaireRepository $commentaireRepo
    ): Response {
        
        // ========== STATISTIQUES DU FORUM ==========
        $forums        = $forumRepo->findAll();
        $publications  = $publicationRepo->findAll();
        $commentaires  = $commentaireRepo->findAll();

        $totalForums        = count($forums);
        $totalPublications  = count($publications);
        $totalCommentaires  = count($commentaires);

        $avgPublicationsParForum = $totalForums > 0 ? round($totalPublications / $totalForums, 1) : 0;

        // Forum le plus actif
        $forumPlusActif = null;
        $maxActivite    = 0;
        foreach ($forums as $forum) {
            $nbPublications = count($forum->getPublications());
            $nbCommentaires = 0;
            foreach ($forum->getPublications() as $pub) {
                $nbCommentaires += count($pub->getCommentaires());
            }
            $totalActivite = $nbPublications + $nbCommentaires;
            if ($totalActivite > $maxActivite) {
                $maxActivite    = $totalActivite;
                $forumPlusActif = [
                    'nom'          => $forum->getNom(),
                    'publications' => $nbPublications,
                    'commentaires' => $nbCommentaires,
                ];
            }
        }

        // Top 5 forums par publications
        $topForumsPublications = [];
        foreach ($forums as $forum) {
            $topForumsPublications[] = [
                'nom'   => $forum->getNom(),
                'count' => count($forum->getPublications()),
            ];
        }
        usort($topForumsPublications, fn($a, $b) => $b['count'] <=> $a['count']);
        $topForumsPublications = array_slice($topForumsPublications, 0, 5);

        // Dernières publications et commentaires
        $dernieres_publications = $publicationRepo->findBy([], ['dateCreation' => 'DESC'], 5);
        $derniers_commentaires  = $commentaireRepo->findBy([], ['dateCreation' => 'DESC'], 5);

        // Données pour l'évolution des publications (courbe)
        $evolution_week  = [4, 6, 8, 5, 7, 9, 12];
        $evolution_month = [15, 18, 22, 25, 28, 30, 35, 38, 42, 45, 48, 52, 55, 58, 62, 65, 68, 72, 75, 78, 82, 85, 88, 92, 95, 98, 102, 105, 108, 112];
        $evolution_year  = [45, 52, 68, 85, 102, 125, 148, 172, 195, 218, 245, 278];
        
        $evolution_week_labels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

        // ========== DONNÉES POUR L'ÉVOLUTION DES COMMENTAIRES (AJOUTÉ) ==========
        // À adapter selon tes vraies données de commentaires par jour
        $evolution_week_comments = [2, 3, 5, 4, 6, 8, 10];

        // ========== STATISTIQUES VOYAGES ET PRODUITS ==========
        
        // ── KPI existants ──
        $totalUsers        = $connection->fetchOne("SELECT COUNT(*) FROM utilisateur");
        $totalEvenements   = $connection->fetchOne("SELECT COUNT(*) FROM Evenement");
        $totalReservations = $connection->fetchOne("SELECT COUNT(*) FROM reservationprog");
        $totalRevenue      = $connection->fetchOne("SELECT COALESCE(SUM(prixProg), 0) FROM reservationprog");

        // ── KPI produits ──
        $totalProduits   = $connection->fetchOne("SELECT COUNT(*) FROM produit");
        $totalCommandes  = $connection->fetchOne("SELECT COUNT(*) FROM commande");
        $revenueProduits = $connection->fetchOne("SELECT COALESCE(SUM(Total), 0) FROM commande");

        // ── Graphique 1 : CA par programme ──
        $caParProgramme = $connection->fetchAllAssociative("
            SELECT p.nom as label, COALESCE(SUM(r.prixProg), 0) as total
            FROM programmes p
            LEFT JOIN reservationprog r ON r.idP = p.idProg
            GROUP BY p.idProg, p.nom
            ORDER BY total DESC
            LIMIT 7
        ");

        // ── Graphique 2 : Inscriptions par mois ──
        $inscriptionsParMois = $connection->fetchAllAssociative("
            SELECT 
                DATE_FORMAT(date_inscription, '%b %Y') as mois,
                DATE_FORMAT(date_inscription, '%Y-%m') as mois_sort,
                COUNT(*) as count
            FROM utilisateur
            WHERE date_inscription IS NOT NULL
            GROUP BY mois, mois_sort
            ORDER BY mois_sort ASC
            LIMIT 12
        ");

        // ── Graphique 3 : Top événements ──
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

        // ── Graphique 4 : Top produits les plus commandés ──
        $topProduits = $connection->fetchAllAssociative("
            SELECT p.Titre as label, COALESCE(SUM(cp.Quantite), 0) as total_commande
            FROM produit p
            LEFT JOIN commande_produit cp ON cp.IDPR = p.IDPR
            GROUP BY p.IDPR, p.Titre
            ORDER BY total_commande DESC
            LIMIT 6
        ");

        // ── Graphique 5 : Commandes par mois ──
        $commandesParMois = $connection->fetchAllAssociative("
            SELECT 
                DATE_FORMAT(DateC, '%b %Y') as mois,
                DATE_FORMAT(DateC, '%Y-%m') as mois_sort,
                COUNT(*) as count,
                COALESCE(SUM(Total), 0) as total
            FROM commande
            GROUP BY mois, mois_sort
            ORDER BY mois_sort ASC
            LIMIT 12
        ");

        // ── Graphique 6 : Répartition par catégorie ──
        $produitsParCategorie = $connection->fetchAllAssociative("
            SELECT 
                COALESCE(Categorie, 'Non classé') as label,
                COUNT(*) as count
            FROM produit
            GROUP BY Categorie
            ORDER BY count DESC
        ");

        // ── Graphique 7 : TOP HÔTELS ──
        $topHotels = $connection->fetchAllAssociative("
            SELECT h.nom as label, COUNT(r.idRes) as total_reservations
            FROM hotel h
            JOIN chambre c ON c.idH = h.idH
            JOIN reservation_chambre r ON r.idCh = c.idCh
            GROUP BY h.idH, h.nom
            ORDER BY total_reservations DESC
            LIMIT 6
        ");

        return $this->render('admin/dashboard/index.html.twig', [
            // Données du forum
            'totalForums'             => $totalForums,
            'totalPublications'       => $totalPublications,
            'totalCommentaires'       => $totalCommentaires,
            'avgParForum'             => $avgPublicationsParForum,
            'forumPlusActif'          => $forumPlusActif,
            'topForumsPublications'   => $topForumsPublications,
            'dernieres_publications'  => $dernieres_publications,
            'derniers_commentaires'   => $derniers_commentaires,
            'evolution_week'          => $evolution_week,
            'evolution_month'         => $evolution_month,
            'evolution_year'          => $evolution_year,
            'evolution_week_labels'   => $evolution_week_labels,
            'evolution_week_comments' => $evolution_week_comments, // ← AJOUTÉ
            
            // Données voyages et produits
            'stats' => [
                'users'        => $totalUsers,
                'evenements'   => $totalEvenements,
                'reservations' => $totalReservations,
                'revenue'      => number_format($totalRevenue, 0, ',', ' '),
                'produits'         => $totalProduits,
                'commandes'        => $totalCommandes,
                'revenue_produits' => number_format($revenueProduits, 0, ',', ' '),
            ],
            'ca_par_programme'       => $caParProgramme,
            'inscriptions_par_mois'  => $inscriptionsParMois,
            'top_evenements'         => $topEvenements,
            'top_produits'           => $topProduits,
            'commandes_par_mois'     => $commandesParMois,
            'produits_par_categorie' => $produitsParCategorie,
            'top_hotels'             => $topHotels,
        ]);
    }
}