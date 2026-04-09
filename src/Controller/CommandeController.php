<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class CommandeController extends AbstractController
{
    // ✅ Clé de session unique par user : "panier_42", "panier_7", etc.
    private function getPanierKey(): string
    {
        $user = $this->getUser();
        return $user ? 'panier_' . $user->getId() : 'panier_guest';
    }

    // ─── PANIER : afficher ───────────────────────────────────────────────────
    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(SessionInterface $session, ProduitRepository $produitRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $session->get($this->getPanierKey(), []);
        $items  = [];
        $total  = 0;

        foreach ($panier as $id => $quantite) {
            $produit = $produitRepository->find($id);
            if ($produit) {
                $sousTotal = $produit->getPrix() * $quantite;
                $items[]   = ['produit' => $produit, 'quantite' => $quantite, 'sousTotal' => $sousTotal];
                $total    += $sousTotal;
            }
        }

        return $this->render('panier/index.html.twig', ['items' => $items, 'total' => $total]);
    }

    // ─── PANIER : ajouter ────────────────────────────────────────────────────
    #[Route('/ajouter/{id}', name: 'app_panier_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(int $id, SessionInterface $session, ProduitRepository $produitRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $produit = $produitRepository->find($id);
        if (!$produit) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_produit_index');
        }

        $key         = $this->getPanierKey();
        $panier      = $session->get($key, []);
        $panier[$id] = ($panier[$id] ?? 0) + 1;
        $session->set($key, $panier);

        $this->addFlash('success', '« ' . $produit->getTitre() . ' » ajouté au panier.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── PANIER : modifier quantité ──────────────────────────────────────────
    #[Route('/modifier/{id}', name: 'app_panier_modifier', methods: ['POST'])]
    public function modifier(int $id, Request $request, SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $quantite = (int) $request->request->get('quantite', 1);
        $key      = $this->getPanierKey();
        $panier   = $session->get($key, []);

        if ($quantite <= 0) unset($panier[$id]);
        else $panier[$id] = $quantite;

        $session->set($key, $panier);
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── PANIER : supprimer un article ──────────────────────────────────────
    #[Route('/supprimer/{id}', name: 'app_panier_supprimer', methods: ['GET', 'POST'])]
    public function supprimer(int $id, SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $key    = $this->getPanierKey();
        $panier = $session->get($key, []);
        unset($panier[$id]);
        $session->set($key, $panier);

        $this->addFlash('success', 'Article retiré du panier.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── PANIER : vider ──────────────────────────────────────────────────────
    #[Route('/vider', name: 'app_panier_vider', methods: ['GET', 'POST'])]
    public function vider(SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $session->remove($this->getPanierKey());
        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── CHECKOUT ────────────────────────────────────────────────────────────
    #[Route('/valider', name: 'app_panier_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        ProduitRepository $produitRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $key  = $this->getPanierKey();

        $panier                 = $session->get($key, []);
        $items                  = [];
        $total                  = 0;
        $quantiteTotaleProduits = 0;

        foreach ($panier as $id => $quantite) {
            $produit = $produitRepository->find($id);
            if ($produit) {
                $sousTotal = $produit->getPrix() * $quantite;
                $items[]   = ['produit' => $produit, 'quantite' => (int) $quantite, 'sousTotal' => $sousTotal];
                $total                  += $sousTotal;
                $quantiteTotaleProduits += (int) $quantite;
            }
        }

        if (empty($items)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        $commande = new Commande();
        $form     = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commande->setDateC(new \DateTime());
            $commande->setStatut('En attente');
            $commande->setTotal((float) $total);
            $commande->setQuantite($quantiteTotaleProduits);
            $commande->setUser($user);

            $em->persist($commande);

            foreach ($items as $item) {
                $ligne = new CommandeProduit();
                $ligne->setCommande($commande);
                $ligne->setProduit($item['produit']);
                $ligne->setQuantite($item['quantite']);
                $em->persist($ligne);
            }

            $em->flush();

            // ✅ Vider UNIQUEMENT le panier de ce user
            $session->remove($key);

            $this->addFlash('success', 'Félicitations ! Votre commande a bien été enregistrée.');
            return $this->redirectToRoute('app_mes_commandes');
        }

        return $this->render('panier/checkout.html.twig', [
            'form'  => $form->createView(),
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ─── Mes commandes (user connecté seulement) ─────────────────────────────
    #[Route('/mes-commandes', name: 'app_mes_commandes', methods: ['GET'])]
    public function mesCommandes(CommandeRepository $commandeRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $commandes = $commandeRepository->findBy(
            ['user' => $user],
            ['id'   => 'DESC']
        );

        return $this->render('commande/mes_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    // ─── Admin : toutes les commandes ────────────────────────────────────────
    #[Route('/commandes', name: 'app_commande_index', methods: ['GET'])]
    public function listeCommandes(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandeRepository->findAll(),
        ]);
    }
}