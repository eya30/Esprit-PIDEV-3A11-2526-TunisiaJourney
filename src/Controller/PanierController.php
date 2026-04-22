<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Form\CommandeType;
use App\Service\StripeService; 
use App\Repository\CommandeRepository;
use App\Repository\CommandeProduitRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class PanierController extends AbstractController
{
    // ── Afficher le panier ──────────────────────────────────────────────────
    #[Route('/', name: 'app_panier_index')]
    public function index(CommandeProduitRepository $cpRepo): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $items = $cpRepo->findPanierByUser($user);
        $total = $cpRepo->getTotalPanier($user);

        return $this->render('panier/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ── Ajouter au panier ───────────────────────────────────────────────────
    #[Route('/add/{id}', name: 'app_panier_add')]
    public function add(
        int $id,
        ProduitRepository $produitRepo,
        CommandeProduitRepository $cpRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $produit = $produitRepo->find($id);

        if (!$produit) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_produit_index');
        }

        if (!$produit->isDisponibilite()) {
            $this->addFlash('warning', '« ' . $produit->getTitre() . ' » est indisponible.');
            return $this->redirectToRoute('app_produit_index');
        }

        if ($produit->isEnRupture()) {
            $this->addFlash('danger', '« ' . $produit->getTitre() . ' » est en rupture de stock.');
            return $this->redirectToRoute('app_produit_index');
        }

        $item        = $cpRepo->findPanierItem($user, $id);
        $nouvelleQte = ($item ? $item->getQuantite() : 0) + 1;

        if (!$produit->isCommandable($nouvelleQte)) {
            $this->addFlash('warning', 'Stock insuffisant. Seulement ' . $produit->getStock() . ' unité(s) disponible(s).');
            return $this->redirectToRoute('app_produit_index');
        }

        if ($item) {
            $item->setQuantite($nouvelleQte);
        } else {
            $item = new CommandeProduit();
            $item->setUser($user);
            $item->setProduit($produit);
            $item->setQuantite(1);
            $item->setIsPanier(true);
            $item->setCommande(null);
            $em->persist($item);
        }

        $em->flush();

        if ($produit->isStockFaible()) {
            $this->addFlash('warning', '⚠️ « ' . $produit->getTitre() . ' » ajouté — stock faible (' . $produit->getStock() . ' restant(s)).');
        } else {
            $this->addFlash('success', '« ' . $produit->getTitre() . ' » ajouté au panier.');
        }

        return $this->redirectToRoute('app_produit_index');
    }

    // ── Modifier la quantité ─────────────────────────────────────────────────
    #[Route('/modifier/{id}', name: 'app_panier_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        CommandeProduitRepository $cpRepo,
        ProduitRepository $produitRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $quantite = (int) $request->request->get('quantite', 1);
        $item     = $cpRepo->findPanierItem($user, $id);

        if ($item) {
            if ($quantite <= 0) {
                $em->remove($item);
            } else {
                $produit = $produitRepo->find($id);
                if ($produit && !$produit->isCommandable($quantite)) {
                    $this->addFlash('warning', 'Stock insuffisant. Maximum : ' . $produit->getStock() . ' unité(s).');
                    return $this->redirectToRoute('app_panier_index');
                }
                $item->setQuantite($quantite);
            }
            $em->flush();
        }

        return $this->redirectToRoute('app_panier_index');
    }

    // ── Supprimer un article ─────────────────────────────────────────────────
    #[Route('/supprimer/{id}', name: 'app_panier_remove')]
    public function remove(int $id, CommandeProduitRepository $cpRepo, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $item = $cpRepo->findPanierItem($user, $id);

        if ($item) {
            $em->remove($item);
            $em->flush();
            $this->addFlash('success', 'Article retiré du panier.');
        }

        return $this->redirectToRoute('app_panier_index');
    }

    // ── Vider le panier ──────────────────────────────────────────────────────
    #[Route('/vider', name: 'app_panier_clear')]
    public function clear(CommandeProduitRepository $cpRepo, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $cpRepo->clearPanier($user);
        $em->flush();

        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ── Finaliser la commande ────────────────────────────────────────────────
    #[Route('/checkout', name: 'app_panier_checkout')]
public function checkout(
    Request $request,
    CommandeProduitRepository $cpRepo,
    EntityManagerInterface $em,
    StripeService $stripeService
): Response {
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_login');
    }

    /** @var \App\Entity\User $user */
    $user  = $this->getUser();
    $items = $cpRepo->findPanierByUser($user);

    if (empty($items)) {
        $this->addFlash('error', 'Votre panier est vide.');
        return $this->redirectToRoute('app_panier_index');
    }

    // Vérifier stock
    $erreurs = [];
    foreach ($items as $item) {
        $produit = $item->getProduit();
        if (!$produit->isCommandable($item->getQuantite())) {
            $erreurs[] = '« ' . $produit->getTitre() . ' » : stock insuffisant.';
        }
    }
    if (!empty($erreurs)) {
        foreach ($erreurs as $erreur) {
            $this->addFlash('danger', $erreur);
        }
        return $this->redirectToRoute('app_panier_index');
    }

    // Calcul du total initial
    $totalInitial = $cpRepo->getTotalPanier($user);
    $remise = 0;

    // ✅ Remise automatique : 10% si total > 100 DT
    if ($totalInitial > 100) {
        $remise = $totalInitial * 0.10;
    }
    $totalFinal = $totalInitial - $remise;

    // Création de la commande (le total enregistré est le total après remise)
    $commande = new Commande();
    $commande->setDateC(new \DateTime());
    $commande->setStatut('En attente');
    $commande->setTotal($totalFinal);
    $commande->setQuantite(array_sum(array_map(fn($i) => $i->getQuantite(), $items)));
    $commande->setUser($user);

    $form = $this->createForm(CommandeType::class, $commande);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($commande);
        $em->flush();

        foreach ($items as $item) {
            $item->getProduit()->decrementStock($item->getQuantite());
            $item->setCommande($commande);
            $item->setIsPanier(false);
            $item->setUser(null);
        }
        $em->flush();

        $this->addFlash('success', 'Commande passée avec succès ! 🎉');
        return $this->redirectToRoute('app_commande_index');
    }

    return $this->render('panier/checkout.html.twig', [
        'form'          => $form->createView(),
        'items'         => $items,
        'totalInitial'  => $totalInitial,
        'remise'        => $remise,
        'total'         => $totalFinal,
        'stripe_public_key' => $stripeService->getPublicKey(),
    ]);
}
}