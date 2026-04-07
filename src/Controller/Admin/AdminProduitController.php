<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/produit')]
class AdminProduitController extends AbstractController
{
    #[Route('/', name: 'admin_produit_index')]
    public function index(ProduitRepository $repo): Response
    {
        return $this->render('admin/produit/index.html.twig', [
            'produits' => $repo->findAll(),
        ]);
    }

    #[Route('/nouveau', name: 'admin_produit_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image pour l'ajout
            $file = $form->get('image')->getData();
            if ($file) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/produits',
                    $fileName
                );
                $produit->setImage($fileName);
            }

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('admin_produit_index');
        }

        return $this->render('admin/produit/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'Ajouter',
            'produit' => $produit
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_produit_edit')]
    public function edit(Produit $produit, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('image')->getData();
            if ($file) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/produits',
                    $fileName
                );
                $produit->setImage($fileName);
            }

            $em->flush();

            $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('admin_produit_index');
        }

        return $this->render('admin/produit/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'Modifier',
            'produit' => $produit
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_produit_delete', methods: ['POST'])]
    public function delete(Produit $produit, EntityManagerInterface $em): Response
    {
        $em->remove($produit);
        $em->flush();

        $this->addFlash('success', 'Produit supprimé avec succès !');
        return $this->redirectToRoute('admin_produit_index');
    }
}