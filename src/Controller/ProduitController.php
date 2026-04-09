<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    // Liste publique — tous les produits
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $repo): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $repo->findAll(),
        ]);
    }

    // ✅ Mes produits — uniquement ceux du user connecté
    #[Route('/mes-produits', name: 'app_mes_produits', methods: ['GET'])]
    public function mesProduits(ProduitRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $produits = $repo->findBy(
            ['user' => $user],
            ['idPR' => 'DESC']
        );

        return $this->render('produit/mes_produits.html.twig', [
            'produits' => $produits,
        ]);
    }

    // ✅ Nouveau produit — lié au user connecté
    #[Route('/nouveau', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $produit = new Produit();
        $form    = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $safeFilename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $fileName     = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('produits_directory'), $fileName);
                $produit->setImage($fileName);
            }

            // ✅ Lier le produit au user connecté
            $produit->setUser($user);

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('app_produit_index');
        }

        return $this->render('produit/_form.html.twig', [
            'form'    => $form->createView(),
            'produit' => $produit,
        ]);
    }

    // Modifier — seulement si c'est le propriétaire
    #[Route('/{idPR}/modifier', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Produit $produit, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // ✅ Sécurité : seul le propriétaire peut modifier
        if ($produit->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier ce produit.');
            return $this->redirectToRoute('app_produit_index');
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $safeFilename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $fileName     = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('produits_directory'), $fileName);
                $produit->setImage($fileName);
            }

            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('app_produit_index');
        }

        return $this->render('produit/_form.html.twig', [
            'form'    => $form->createView(),
            'produit' => $produit,
        ]);
    }

    // Supprimer — seulement si c'est le propriétaire
    #[Route('/{idPR}/supprimer', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // ✅ Sécurité : seul le propriétaire peut supprimer
        if ($produit->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer ce produit.');
            return $this->redirectToRoute('app_produit_index');
        }

        if ($this->isCsrfTokenValid('delete' . $produit->getIdPR(), $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }

        return $this->redirectToRoute('app_produit_index');
    }
}