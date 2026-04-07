<?php
namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Entity\Produit;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class PanierController extends AbstractController
{
    // ── Afficher le panier ──────────────────────────────────────────────────
    #[Route('/', name: 'app_panier_index')]
    public function index(SessionInterface $session, ProduitRepository $repo): Response
    {
        $panier = $session->get('panier', []);
        [$items, $total] = $this->buildItems($panier, $repo);

        return $this->render('panier/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ── Ajouter au panier ───────────────────────────────────────────────────
    #[Route('/ajouter/{id}', name: 'app_panier_add')]
    public function add(Produit $produit, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        $id     = $produit->getId();
        $panier[$id] = ($panier[$id] ?? 0) + 1;
        $session->set('panier', $panier);

        $this->addFlash('success', '« ' . $produit->getTitre() . ' » ajouté au panier !');
        return $this->redirectToRoute('app_produit_index');
    }

    // ── Mettre à jour la quantité ───────────────────────────────────────────
    #[Route('/modifier/{id}/{quantite}', name: 'app_panier_update')]
    public function update(int $id, int $quantite, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        if ($quantite <= 0) {
            unset($panier[$id]);
        } else {
            $panier[$id] = $quantite;
        }
        $session->set('panier', $panier);
        return $this->redirectToRoute('app_panier_index');
    }

    // ── Supprimer un article ────────────────────────────────────────────────
    #[Route('/supprimer/{id}', name: 'app_panier_remove')]
    public function remove(int $id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        unset($panier[$id]);
        $session->set('panier', $panier);
        $this->addFlash('success', 'Article retiré du panier.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ── Vider le panier ─────────────────────────────────────────────────────
    #[Route('/vider', name: 'app_panier_clear')]
    public function clear(SessionInterface $session): Response
    {
        $session->set('panier', []);
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
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        [$items, $total] = $this->buildItems($panier, $repo);

        $commande = new Commande();
        $commande->setDateC(new \DateTime());
        $commande->setStatut('En attente');
        $commande->setTotal($total);
        // Quantite globale = somme des quantités
        $commande->setQuantite(array_sum($panier));

        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalcul total côté serveur
            [, $totalServeur] = $this->buildItems($panier, $repo);
            $commande->setTotal($totalServeur);

            $em->persist($commande);

            // Créer les lignes commande_produit
            foreach ($panier as $idProduit => $quantite) {
                $produit = $repo->find($idProduit);
                if ($produit) {
                    $ligne = new CommandeProduit();
                    $ligne->setCommande($commande);
                    $ligne->setProduit($produit);
                    $ligne->setQuantite($quantite);
                    $em->persist($ligne);
                }
            }

            $em->flush();
            $session->set('panier', []);

            $this->addFlash('success', 'Commande passée avec succès ! Merci pour votre achat. 🎉');
            return $this->redirectToRoute('app_commande_index');
        }

        return $this->render('panier/checkout.html.twig', [
            'form'  => $form->createView(),
            'items' => $items,
            'total' => $total,
        ]);
    }

    // ── Liste des commandes ─────────────────────────────────────────────────
    #[Route('/commandes', name: 'app_commande_index')]
    public function commandes(CommandeRepository $repo): Response
    {
        return $this->render('panier/commandes.html.twig', [
            'commandes' => $repo->findAll(),
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

    // ── Helper : construire les items depuis la session ─────────────────────
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
