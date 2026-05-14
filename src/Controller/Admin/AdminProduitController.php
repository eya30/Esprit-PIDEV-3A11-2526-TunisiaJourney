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
<<<<<<< HEAD
use Symfony\Component\Form\FormInterface;
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/produit')]
class AdminProduitController extends AbstractController
{
    #[Route('', name: 'admin_produit_index')]
    public function index(ProduitRepository $repo, Request $request, PaginatorInterface $paginator): Response
    {
<<<<<<< HEAD
        $searchTerm = $request->query->getString('q') ?: null;
        $cat        = $request->query->getString('category') ?: null;
        $sortBy     = $request->query->getString('sort', 'p.idPR');
        $direction  = $request->query->getString('direction', 'desc');
=======
        $searchTerm = $request->query->get('q', null);
        $cat        = $request->query->get('category', null);
        $sortBy     = $request->query->get('sort', 'p.idPR');
        $direction  = $request->query->get('direction', 'desc');
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

        $pagination = $paginator->paginate(
            $repo->getQueryForPagination($searchTerm, $cat, $sortBy, $direction),
            $request->query->getInt('page', 1),
            10
        );

<<<<<<< HEAD
        $produitsReappro = $repo->findNeedsReappro();

        return $this->render('admin/produit/index.html.twig', [
            'produits'         => $pagination,
            'lastSearch'       => $searchTerm,
            'currentSort'      => $sortBy,
            'currentDirection' => $direction,
            'produitsReappro'  => $produitsReappro,
=======
        // ✅ Produits nécessitant réapprovisionnement
        $produitsReappro = $repo->findNeedsReappro();

        return $this->render('admin/produit/index.html.twig', [
            'produits'        => $pagination,
            'lastSearch'      => $searchTerm,
            'currentSort'     => $sortBy,
            'currentDirection'=> $direction,
            'produitsReappro' => $produitsReappro, // ✅ Suggestions réappro
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        ]);
    }

    #[Route('/nouveau', name: 'admin_produit_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form    = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImage($form, $produit);
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('admin_produit_index');
        }

        return $this->render('admin/produit/form.html.twig', [
            'form'    => $form->createView(),
            'action'  => 'Ajouter',
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_produit_edit')]
    public function edit(Produit $produit, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImage($form, $produit);
            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('admin_produit_index');
        }

        return $this->render('admin/produit/form.html.twig', [
            'form'    => $form->createView(),
            'action'  => 'Modifier',
            'produit' => $produit,
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

<<<<<<< HEAD
    private function handleImage(FormInterface $form, Produit $produit): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file) return;

        $projectDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \RuntimeException('kernel.project_dir parameter is not a string.');
        }
        $uploadDir = $projectDir . '/public/uploads/produits';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

=======
    private function handleImage($form, Produit $produit): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file) return;

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/produits';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $ancienneImage = $produit->getImage();
        if ($ancienneImage && file_exists($uploadDir . '/' . $ancienneImage)) {
            unlink($uploadDir . '/' . $ancienneImage);
        }

        $fileName = uniqid('produit_') . '.' . ($file->guessExtension() ?? 'jpg');
        $file->move($uploadDir, $fileName);
        $produit->setImage($fileName);
    }
}