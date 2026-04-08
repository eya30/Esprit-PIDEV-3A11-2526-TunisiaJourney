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
    // ─── PANIER : afficher ────────────────────────────────────────────────────
    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(
        SessionInterface $session,
        ProduitRepository $produitRepository
    ): Response {
        $panier = $session->get('panier', []);
        $items  = [];
        $total  = 0;

        foreach ($panier as $id => $quantite) {
            $produit = $produitRepository->find($id);
            if ($produit) {
                $sousTotal = $produit->getPrix() * $quantite;
                $items[]   = [
                    'produit'   => $produit,
                    'quantite'  => $quantite,
                    'sousTotal' => $sousTotal,
                ];
                $total += $sousTotal;
            }
        }

        return $this->render('panier/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ─── PANIER : ajouter un produit ──────────────────────────────────────────
    #[Route('/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST', 'GET'])]
    public function ajouter(
        int $id,
        SessionInterface $session,
        ProduitRepository $produitRepository
    ): Response {
        $produit = $produitRepository->find($id);

        if (!$produit) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_produit_index');
        }

        $panier = $session->get('panier', []);
        $panier[$id] = ($panier[$id] ?? 0) + 1;
        $session->set('panier', $panier);

        $this->addFlash('success', '«&nbsp;' . $produit->getTitre() . '&nbsp;» ajouté au panier.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── PANIER : modifier la quantité ───────────────────────────────────────
    #[Route('/modifier/{id}', name: 'app_panier_modifier', methods: ['POST'])]
    public function modifier(
        int $id,
        Request $request,
        SessionInterface $session
    ): Response {
        $quantite = (int) $request->request->get('quantite', 1);
        $panier   = $session->get('panier', []);

        if ($quantite <= 0) {
            unset($panier[$id]);
        } else {
            $panier[$id] = $quantite;
        }

        $session->set('panier', $panier);
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── PANIER : supprimer un article ───────────────────────────────────────
    #[Route('/supprimer/{id}', name: 'app_panier_supprimer', methods: ['POST', 'GET'])]
    public function supprimer(int $id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        unset($panier[$id]);
        $session->set('panier', $panier);

        $this->addFlash('success', 'Article retiré du panier.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── PANIER : vider ───────────────────────────────────────────────────────
    #[Route('/vider', name: 'app_panier_vider', methods: ['POST', 'GET'])]
    public function vider(SessionInterface $session): Response
    {
        $session->remove('panier');
        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ─── CHECKOUT : valider la commande ──────────────────────────────────────
    #[Route('/valider', name: 'app_panier_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        ProduitRepository $produitRepository
    ): Response {
        // 1. Construire les items depuis la session
        $panier                 = $session->get('panier', []);
        $items                  = [];
        $total                  = 0;
        $quantiteTotaleProduits = 0;

        foreach ($panier as $id => $quantite) {
            $produit = $produitRepository->find($id);
            if ($produit) {
                $sousTotal = $produit->getPrix() * $quantite;
                $items[]   = [
                    'produit'   => $produit,
                    'quantite'  => (int) $quantite,
                    'sousTotal' => $sousTotal,
                ];
                $total                  += $sousTotal;
                $quantiteTotaleProduits += (int) $quantite;
            }
        }

        if (empty($items)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        // 2. Créer le formulaire
        $commande = new Commande();
        $form     = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        // 3. Traiter la soumission
        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ ORDRE CORRECT : tout setter AVANT persist
            $commande->setDateC(new \DateTime());
            $commande->setStatut('En attente');
            $commande->setTotal((float) $total);
            $commande->setQuantite($quantiteTotaleProduits); // ← valeur garantie non-null

            $em->persist($commande);

            // Créer les lignes CommandeProduit
            foreach ($items as $item) {
                $ligne = new CommandeProduit();
                $ligne->setCommande($commande);
                $ligne->setProduit($item['produit']);
                $ligne->setQuantite($item['quantite']); // ← int non-null
                $em->persist($ligne);
            }

            $em->flush();

            // Vider le panier après succès
            $session->remove('panier');

            $this->addFlash('success', 'Félicitations ! Votre commande a bien été enregistrée.');
            return $this->redirectToRoute('app_commande_index');
        }

        // 4. Afficher le formulaire
        return $this->render('panier/checkout.html.twig', [
            'form'  => $form->createView(),
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ─── COMMANDES : liste ───────────────────────────────────────────────────
    #[Route('/commandes', name: 'app_commande_index', methods: ['GET'])]
    public function listeCommandes(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandeRepository->findAll(),
        ]);
    }
}