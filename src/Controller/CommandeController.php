<?php
namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    // Liste de toutes les commandes
    #[Route('/', name: 'app_commande_index')]
    public function index(CommandeRepository $repo): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $repo->findAll(),
        ]);
    }

    // Créer une commande depuis un produit
    #[Route('/nouveau/{id}', name: 'app_commande_new')]
    public function new(Produit $produit, Request $request, EntityManagerInterface $em): Response
    {
        $commande = new Commande();
        $commande->setDateC(new \DateTime());
        $commande->setStatut('En attente');
        $commande->setTotal($produit->getPrix());

        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculer le total selon quantité × prix produit
            $commande->setTotal($commande->getQuantite() * $produit->getPrix());
            $em->persist($commande);
            $em->flush();
            $this->addFlash('success', 'Commande passée avec succès !');
            return $this->redirectToRoute('app_commande_index');
        }

        return $this->render('commande/form.html.twig', [
            'form'    => $form->createView(),
            'action'  => 'Passer',
            'produit' => $produit,
        ]);
    }

    // Modifier une commande
    #[Route('/{id}/modifier', name: 'app_commande_edit')]
    public function edit(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commande modifiée avec succès !');
            return $this->redirectToRoute('app_commande_index');
        }

        return $this->render('commande/form.html.twig', [
            'form'    => $form->createView(),
            'action'  => 'Modifier',
            'produit' => null,
        ]);
    }

    // Supprimer une commande
    #[Route('/{id}/supprimer', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Commande $commande, EntityManagerInterface $em): Response
    {
        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Commande supprimée avec succès !');
        return $this->redirectToRoute('app_commande_index');
    }
}
