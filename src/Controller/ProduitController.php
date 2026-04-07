<?php
namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index')]
    public function index(ProduitRepository $repo): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $repo->findAll()
        ]);
    }

    #[Route('/nouveau', name: 'app_produit_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('image')->getData();
            if ($file) {
                $fileName = uniqid().'.'.$file->guessExtension();
                $file->move($this->getParameter('kernel.project_dir').'/public/uploads/produits', $fileName);
                $produit->setImage($fileName);
            }
            $em->persist($produit);
            $em->flush();
            return $this->redirectToRoute('app_produit_index');
        }

        return $this->render('produit/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'Ajouter'
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_produit_edit')]
    public function edit(Produit $produit, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_produit_index');
        }

        return $this->render('produit/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'Modifier'
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_produit_delete')]
    public function delete(Produit $produit, EntityManagerInterface $em): Response
    {
        $em->remove($produit);
        $em->flush();
        return $this->redirectToRoute('app_produit_index');
    }
}