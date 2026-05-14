<?php

namespace App\Controller;

use App\Entity\Commande;
<<<<<<< HEAD
use App\Entity\Produit;
use App\Repository\CommandeRepository;
use App\Repository\CommandeProduitRepository;
use Doctrine\DBAL\Connection;
=======
use App\Repository\CommandeRepository;
use App\Repository\CommandeProduitRepository;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index')]
    public function index(CommandeRepository $repo): Response
    {
        if (!$this->getUser()) return $this->redirectToRoute('app_login');
        $commandes = $repo->findBy(['user' => $this->getUser()], ['id' => 'DESC']);
        return $this->render('commande/index.html.twig', ['commandes' => $commandes]);
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande, CommandeProduitRepository $cpRepo): Response
    {
        if ($commande->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès refusé.');
<<<<<<< HEAD
            return $this->redirectToRoute('app_commande_index');
        }
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
            'items'    => $cpRepo->findBy(['commande' => $commande]),
        ]);
    }

    #[Route('/{id}/tracking', name: 'app_commande_tracking', methods: ['GET'])]
    public function tracking(Commande $commande): Response
    {
        if (!$this->getUser()) return $this->redirectToRoute('app_login');
        if ($commande->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_commande_index');
        }
        $statutsValides = ['Confirmée', 'En cours', 'Expédiée', 'Livrée'];
        if (!in_array($commande->getStatut(), $statutsValides)) {
            $this->addFlash('info', 'Tracking disponible une fois la commande confirmée.');
            return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
        }
        return $this->render('commande/tracking.html.twig', ['commande' => $commande]);
    }

    #[Route('/{id}/modifier', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_commande_index');
        }
        if ($commande->getStatut() !== 'En attente') {
            $this->addFlash('warning', 'Commande déjà en cours de traitement.');
            return $this->redirectToRoute('app_commande_index');
        }
        $form = $this->createForm(\App\Form\CommandeType::class, $commande);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commande mise à jour.');
            return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
        }
        return $this->render('commande/edit.html.twig', [
            'commande' => $commande, 'form' => $form->createView(),
            'action' => 'Modifier', 'produit' => null,
        ]);
    }

    /**
     * Crée une nouvelle commande avec support du code promo.
     */
    #[Route('/new/{produitId}', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(
        int $produitId,
        Request $request,
        EntityManagerInterface $em,
        Connection $connection
    ): Response {
        if (!$this->getUser()) return $this->redirectToRoute('app_login');

        $produit = $em->getRepository(Produit::class)->find($produitId);
        if (!$produit) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_produit_index');
        }

        $commande = new Commande();
        $form     = $this->createForm(\App\Form\CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $codePromo     = trim(strtoupper((string) $request->request->get('code_promo', '')));
            $quantite      = (int) $commande->getQuantite();
            $prixUnitaire  = $produit->getPrix();
            $total         = $prixUnitaire * $quantite;

            // ========== VALIDATION & APPLICATION DU CODE PROMO ==========
            $reductionPourcentage = 0;
            if ($codePromo !== '') {
                $today = (new \DateTime())->format('Y-m-d');
                $promo = $connection->fetchAssociative(
                    "SELECT * FROM code_promo
                     WHERE code = ? AND statut = 'actif'
                       AND date_debut <= ? AND date_fin >= ?",
                    [$codePromo, $today, $today]
                );

                if ($promo) {
                    $reductionPourcentage = (float) $promo['pourcentage_reduction'];
                    $total                = $total * (1 - $reductionPourcentage / 100);
                    $this->addFlash('success', sprintf(
                        '🎉 Code promo "%s" appliqué ! Réduction de %d%%.',
                        $codePromo,
                        (int)$reductionPourcentage
                    ));
                } else {
                    $this->addFlash('danger', '❌ Code promo invalide, expiré ou inactif.');
                    return $this->render('commande/edit.html.twig', [
                        'commande' => $commande,
                        'form'     => $form->createView(),
                        'action'   => 'Passer',
                        'produit'  => $produit,
                    ]);
                }
            }
            // =============================================================

            $commande->setUser($this->getUser());
            $commande->setStatut('En attente');
            $commande->setTotal($total);

            $em->persist($commande);
            $em->flush();

            $this->addFlash('success', '✅ Commande passée avec succès !');
            return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
        }

        return $this->render('commande/edit.html.twig', [
            'commande' => $commande,
            'form'     => $form->createView(),
            'action'   => 'Passer',
            'produit'  => $produit,
        ]);
    }

    // ========== AJAX : VÉRIFIER UN CODE PROMO ==========
    #[Route('/api/verifier-code-promo-commande', name: 'api_verifier_code_promo_commande', methods: ['POST'])]
    public function verifierCodePromo(Request $request, Connection $connection): Response
    {
        $data     = json_decode($request->getContent(), true);
        $code     = trim(strtoupper((string)($data['code'] ?? '')));
        $prixBase = (float)($data['prix_base'] ?? 0);

        if ($code === '') {
            return $this->json(['valid' => false, 'message' => 'Code vide.']);
        }

        $today = (new \DateTime())->format('Y-m-d');
        $promo = $connection->fetchAssociative(
            "SELECT * FROM code_promo
             WHERE code = ? AND statut = 'actif'
               AND date_debut <= ? AND date_fin >= ?",
            [$code, $today, $today]
        );

        if (!$promo) {
            return $this->json(['valid' => false, 'message' => 'Code invalide, expiré ou inactif.']);
        }

        $reduction  = (float) $promo['pourcentage_reduction'];
        $prixReduit = $prixBase * (1 - $reduction / 100);

        return $this->json([
            'valid'       => true,
            'reduction'   => $reduction,
            'prix_reduit' => round($prixReduit, 2),
            'message'     => sprintf('🎉 Code valide ! Réduction de %d%% appliquée.', (int)$reduction),
        ]);
    }

    #[Route('/{id}/article/{itemId}/quantite', name: 'app_commande_item_update', methods: ['POST'])]
    public function updateItem(Commande $commande, int $itemId, Request $request, CommandeProduitRepository $cpRepo, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) { $this->addFlash('danger', 'Accès refusé.'); return $this->redirectToRoute('app_commande_index'); }
        if ($commande->getStatut() !== 'En attente') { $this->addFlash('warning', 'Commande non modifiable.'); return $this->redirectToRoute('app_commande_index'); }

        $item     = $cpRepo->find($itemId);
        $quantite = (int) $request->request->get('quantite', 1);

        if (!$item || $item->getCommande()?->getId() !== $commande->getId()) {
            $this->addFlash('danger', 'Article introuvable.');
            return $this->redirectToRoute('app_commande_index');
        }

        if ($quantite <= 0) {
            $em->remove($item);
            $em->flush();
            if (empty($cpRepo->findBy(['commande' => $commande]))) {
                $em->remove($commande); $em->flush();
                $this->addFlash('info', 'Commande supprimée (vide).');
                return $this->redirectToRoute('app_commande_index');
            }
        } else {
            $produit  = $item->getProduit();
            $stockMax = ($produit instanceof Produit ? $produit->getStock() : 0) + $item->getQuantite();

            if ($quantite > $stockMax) {
                $this->addFlash('warning', 'Stock insuffisant. Max : ' . $stockMax);
                return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
            }

            $item->setQuantite($quantite);
            $em->flush();
        }

        $this->recalculerTotal($commande, $cpRepo, $em);
        $this->addFlash('success', 'Article mis à jour.');
        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }

    #[Route('/{id}/article/{itemId}/supprimer', name: 'app_commande_item_delete', methods: ['POST'])]
    public function deleteItem(Commande $commande, int $itemId, CommandeProduitRepository $cpRepo, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) { $this->addFlash('danger', 'Accès refusé.'); return $this->redirectToRoute('app_commande_index'); }
        if ($commande->getStatut() !== 'En attente') { $this->addFlash('warning', 'Commande non modifiable.'); return $this->redirectToRoute('app_commande_index'); }

        $item = $cpRepo->find($itemId);
        if ($item && $item->getCommande()?->getId() === $commande->getId()) {
            $em->remove($item);
            $em->flush();
            $this->recalculerTotal($commande, $cpRepo, $em);
        }
        if (empty($cpRepo->findBy(['commande' => $commande]))) {
            $em->remove($commande); $em->flush();
            $this->addFlash('info', 'Commande supprimée (vide).');
            return $this->redirectToRoute('app_commande_index');
        }
        $this->addFlash('success', 'Article supprimé.');
        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Commande $commande, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) { $this->addFlash('danger', 'Accès refusé.'); return $this->redirectToRoute('app_commande_index'); }
        if ($commande->getStatut() !== 'En attente') { $this->addFlash('warning', 'Seules les commandes "En attente" peuvent être supprimées.'); return $this->redirectToRoute('app_commande_index'); }
        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Commande supprimée.');
        return $this->redirectToRoute('app_commande_index');
    }

    private function recalculerTotal(Commande $commande, CommandeProduitRepository $cpRepo, EntityManagerInterface $em): void
    {
        $items = $cpRepo->findBy(['commande' => $commande]);
        $total = 0.0;
        $qte   = 0;

        foreach ($items as $item) {
            $produit = $item->getProduit();
            if (!$produit instanceof Produit) {
                continue;
            }
            $total += $produit->getPrix() * $item->getQuantite();
            $qte   += $item->getQuantite();
        }

        $commande->setTotal($total);
        $commande->setQuantite($qte);
        $em->flush();
=======
            return $this->redirectToRoute('app_commande_index');
        }
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
            'items'    => $cpRepo->findBy(['commande' => $commande]),
        ]);
    }

    // ✅ PAGE TRACKING
    #[Route('/{id}/tracking', name: 'app_commande_tracking', methods: ['GET'])]
    public function tracking(Commande $commande): Response
    {
        if (!$this->getUser()) return $this->redirectToRoute('app_login');
        if ($commande->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_commande_index');
        }
        $statutsValides = ['Confirmée', 'En cours', 'Expédiée', 'Livrée'];
        if (!in_array($commande->getStatut(), $statutsValides)) {
            $this->addFlash('info', 'Tracking disponible une fois la commande confirmée.');
            return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
        }
        return $this->render('commande/tracking.html.twig', ['commande' => $commande]);
    }

    #[Route('/{id}/modifier', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_commande_index');
        }
        if ($commande->getStatut() !== 'En attente') {
            $this->addFlash('warning', 'Commande déjà en cours de traitement.');
            return $this->redirectToRoute('app_commande_index');
        }
        $form = $this->createForm(\App\Form\CommandeType::class, $commande);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commande mise à jour.');
            return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
        }
        return $this->render('commande/edit.html.twig', [
            'commande' => $commande, 'form' => $form->createView(),
            'action' => 'Modifier', 'produit' => null,
        ]);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }

    #[Route('/{id}/article/{itemId}/quantite', name: 'app_commande_item_update', methods: ['POST'])]
    public function updateItem(Commande $commande, int $itemId, Request $request, CommandeProduitRepository $cpRepo, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) { $this->addFlash('danger', 'Accès refusé.'); return $this->redirectToRoute('app_commande_index'); }
        if ($commande->getStatut() !== 'En attente') { $this->addFlash('warning', 'Commande non modifiable.'); return $this->redirectToRoute('app_commande_index'); }
        $item = $cpRepo->find($itemId);
        $quantite = (int) $request->request->get('quantite', 1);
        if (!$item || $item->getCommande()?->getId() !== $commande->getId()) { $this->addFlash('danger', 'Article introuvable.'); return $this->redirectToRoute('app_commande_index'); }
        if ($quantite <= 0) {
            $em->remove($item); $em->flush();
            if (empty($cpRepo->findBy(['commande' => $commande]))) { $em->remove($commande); $em->flush(); $this->addFlash('info', 'Commande supprimée (vide).'); return $this->redirectToRoute('app_commande_index'); }
        } else {
            $stockMax = ($item->getProduit()->getStock() ?? 0) + $item->getQuantite();
            if ($quantite > $stockMax) { $this->addFlash('warning', 'Stock insuffisant. Max : ' . $stockMax); return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]); }
            $item->setQuantite($quantite); $em->flush();
        }
        $this->recalculerTotal($commande, $cpRepo, $em);
        $this->addFlash('success', 'Article mis à jour.');
        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }

    #[Route('/{id}/article/{itemId}/supprimer', name: 'app_commande_item_delete', methods: ['POST'])]
    public function deleteItem(Commande $commande, int $itemId, CommandeProduitRepository $cpRepo, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) { $this->addFlash('danger', 'Accès refusé.'); return $this->redirectToRoute('app_commande_index'); }
        if ($commande->getStatut() !== 'En attente') { $this->addFlash('warning', 'Commande non modifiable.'); return $this->redirectToRoute('app_commande_index'); }
        $item = $cpRepo->find($itemId);
        if ($item && $item->getCommande()?->getId() === $commande->getId()) { $em->remove($item); $em->flush(); $this->recalculerTotal($commande, $cpRepo, $em); }
        if (empty($cpRepo->findBy(['commande' => $commande]))) { $em->remove($commande); $em->flush(); $this->addFlash('info', 'Commande supprimée (vide).'); return $this->redirectToRoute('app_commande_index'); }
        $this->addFlash('success', 'Article supprimé.');
        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Commande $commande, EntityManagerInterface $em): Response
    {
        if ($commande->getUser() !== $this->getUser()) { $this->addFlash('danger', 'Accès refusé.'); return $this->redirectToRoute('app_commande_index'); }
        if ($commande->getStatut() !== 'En attente') { $this->addFlash('warning', 'Seules les commandes "En attente" peuvent être supprimées.'); return $this->redirectToRoute('app_commande_index'); }
        $em->remove($commande); $em->flush();
        $this->addFlash('success', 'Commande supprimée.');
        return $this->redirectToRoute('app_commande_index');
    }

    private function recalculerTotal(Commande $commande, CommandeProduitRepository $cpRepo, EntityManagerInterface $em): void
    {
        $items = $cpRepo->findBy(['commande' => $commande]);
        $total = 0; $qte = 0;
        foreach ($items as $item) { $total += $item->getProduit()->getPrix() * $item->getQuantite(); $qte += $item->getQuantite(); }
        $commande->setTotal($total); $commande->setQuantite($qte); $em->flush();
    }
}