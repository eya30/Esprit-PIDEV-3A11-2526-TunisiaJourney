<?php

namespace App\Controller\Admin;

use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(
        ProduitRepository $produitRepo,
        CommandeRepository $commandeRepo,
        EntityManagerInterface $em
    ): Response {

        // ── Stats globales (Boutique uniquement) ──────────────────────────
        $toutsProduits = $produitRepo->findAll();
        $toutesCommandes = $commandeRepo->findAll();

        $chiffreAffaires = array_reduce(
            $toutesCommandes,
            fn($carry, $c) => $carry + ($c->getTotal() ?? 0),
            0
        );

        // ── Top produits les plus commandés (Requête DQL optimisée) ───────
        $topRaw = $em->createQuery("
            SELECT p.titre AS titre,
                   SUM(cp.quantite) AS totalQte
            FROM App\Entity\CommandeProduit cp
            JOIN cp.produit p
            GROUP BY p.id, p.titre
            ORDER BY totalQte DESC
        ")->setMaxResults(5)->getResult();

        $max = !empty($topRaw) ? (int) $topRaw[0]['totalQte'] : 1;
        $topProduits = array_map(function ($row) use ($max) {
            return [
                'titre'      => $row['titre'],
                'totalQte'   => (int) $row['totalQte'],
                'pourcentage'=> round(((int) $row['totalQte'] / $max) * 100),
            ];
        }, $topRaw);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'produits'        => count($toutsProduits),
                'commandes'       => count($toutesCommandes),
                'chiffreAffaires' => $chiffreAffaires,
            ],
            'top_produits'       => $topProduits,
            'derniers_produits'  => $produitRepo->findBy([], ['id' => 'DESC'], 5),
            'dernieres_commandes'=> $commandeRepo->findBy([], ['id' => 'DESC'], 5),
        ]);
    }
}