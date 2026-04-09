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
class PanierController extends AbstractController
{
    // ✅ Clé unique par user — "panier_3", "panier_7", etc.
    private function getKey(): string
    {
        $user = $this->getUser();
        return $user ? 'panier_' . $user->getId() : 'panier_guest';
    }

    // ── Afficher le panier ──────────────────────────────────────────────────
    #[Route('/', name: 'app_panier_index')]
    public function index(SessionInterface $session, ProduitRepository $repo): Response
    {
        $panier = $session->get($this->getKey(), []);
        [$items, $total] = $this->buildItems($panier, $repo);

        return $this->render('panier/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ── Ajouter au panier ───────────────────────────────────────────────────
    // ✅ SessionInterface au lieu de RequestStack — même instance partout
    #[Route('/ajouter/{id}', name: 'app_panier_add')]
    public function add(int $id, SessionInterface $session, ProduitRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $produit = $repo->find($id);
        if (!$produit) {
            $this->addFlash('danger', 'Produit introuvable.');
           return $this->redirectToRoute('app_produit_index');
        }

        $key         = $this->getKey();
        $panier      = $session->get($key, []);
        $panier[$id] = ($panier[$id] ?? 0) + 1;
        $session->set($key, $panier);

        $this->addFlash('success', '« ' . $produit->getTitre() . ' » ajouté au panier.');
        return $this->redirectToRoute('app_produit_index');
    }

    // ── Mettre à jour la quantité ───────────────────────────────────────────
    #[Route('/modifier/{id}/{quantite}', name: 'app_panier_update')]
    public function update(int $id, int $quantite, SessionInterface $session): Response
    {
        $key    = $this->getKey();
        $panier = $session->get($key, []);

        if ($quantite <= 0) unset($panier[$id]);
        else $panier[$id] = $quantite;

        $session->set($key, $panier);
        return $this->redirectToRoute('app_panier_index');
    }

    // ── Supprimer un article ────────────────────────────────────────────────
    #[Route('/supprimer/{id}', name: 'app_panier_remove')]
    public function remove(int $id, SessionInterface $session): Response
    {
        $key    = $this->getKey();
        $panier = $session->get($key, []);
        unset($panier[$id]);
        $session->set($key, $panier);

        $this->addFlash('success', 'Article retiré du panier.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ── Vider le panier ─────────────────────────────────────────────────────
    #[Route('/vider', name: 'app_panier_clear')]
    public function clear(SessionInterface $session): Response
    {
        $session->remove($this->getKey());
        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ── Finaliser la commande ───────────────────────────────────────────────
    #[Route('/valider', name: 'app_panier_checkout')]
    public function checkout(
        Request $request,
        SessionInterface $session,
        ProduitRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $key  = $this->getKey();

        $panier = $session->get($key, []);
        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        [$items, $total] = $this->buildItems($panier, $repo);

        $commande = new Commande();
        $commande->setDateC(new \DateTime());
        $commande->setStatut('En attente');
        $commande->setTotal($total);
        $commande->setQuantite(array_sum($panier));
        $commande->setUser($user);

        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($commande);

            foreach ($panier as $idProduit => $quantite) {
                $produit = $repo->find($idProduit);
                if ($produit) {
                    $ligne = new CommandeProduit();
                    $ligne->setCommande($commande);
                    $ligne->setProduit($produit);
                    $ligne->setQuantite((int) $quantite);
                    $em->persist($ligne);
                }
            }

            $em->flush();

            // ✅ Vider uniquement le panier de ce user
            $session->remove($key);

            $this->addFlash('success', 'Commande passée avec succès ! 🎉');
            return $this->redirectToRoute('app_commande_index');
        }

        return $this->render('panier/checkout.html.twig', [
            'form'  => $form->createView(),
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ── Liste des commandes du user connecté ────────────────────────────────
    #[Route('/commandes', name: 'app_commande_index')]
    public function commandes(CommandeRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user         = $this->getUser();
        $mesCommandes = $repo->findBy(['user' => $user], ['id' => 'DESC']);

        return $this->render('panier/commandes.html.twig', [
            'commandes' => $mesCommandes,
        ]);
    }

    // ── Supprimer une commande ──────────────────────────────────────────────
    #[Route('/commandes/{id}/supprimer', name: 'app_commande_delete', methods: ['POST'])]
    public function deleteCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Commande supprimée.');
        return $this->redirectToRoute('app_commande_index');
    }

    // ── Helper ──────────────────────────────────────────────────────────────
    private function buildItems(array $panier, ProduitRepository $repo): array
    {
        $items = [];
        $total = 0;
        foreach ($panier as $idProduit => $quantite) {
            $produit = $repo->find($idProduit);
            if ($produit) {
                $sousTotal = $produit->getPrix() * $quantite;
                $items[]   = ['produit' => $produit, 'quantite' => $quantite, 'sousTotal' => $sousTotal];
                $total    += $sousTotal;
            }
        }
        return [$items, $total];
    }
}