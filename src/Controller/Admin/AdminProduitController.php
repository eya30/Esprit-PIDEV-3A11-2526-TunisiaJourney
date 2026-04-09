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
class AdminProduitController extends AbstractController{

#[Route('', name: 'admin_produit_index')]
public function index(ProduitRepository $repo, Request $request): Response
{
    $searchTerm = $request->query->get('q', '');
    $sortBy = $request->query->get('sort', 'idPR');
    $direction = $request->query->get('direction', 'ASC'); // On ajoute la direction

    // Sécurité : on ne trie que sur les champs existants
    $allowedSorts = ['idPR', 'titre', 'prix', 'stock', 'categorie'];
    if (!in_array($sortBy, $allowedSorts)) $sortBy = 'idPR';
    
    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

    // On récupère les résultats
    $produits = $repo->findBySearchAndSort($searchTerm, $sortBy, $direction);

    return $this->render('admin/produit/index.html.twig', [
        'produits' => $produits,
        'lastSearch' => $searchTerm,
        'currentSort' => $sortBy,
        'currentDirection' => $direction
    ]);
}

    #[Route('/nouveau', name: 'admin_produit_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
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

    // ── Helper : gestion de l'upload image ──────────────────────────────
    private function handleImage($form, Produit $produit): void
    {
        $file = $form->get('imageFile')->getData();

        if (!$file) {
            return;
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/produits';

        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Supprimer l'ancienne image si elle existe
        $ancienneImage = $produit->getImage();
        if ($ancienneImage && file_exists($uploadDir . '/' . $ancienneImage)) {
            unlink($uploadDir . '/' . $ancienneImage);
        }

        $extension = $file->guessExtension() ?? 'jpg';
        $fileName  = uniqid('produit_') . '.' . $extension;
        $file->move($uploadDir, $fileName);
        $produit->setImage($fileName);
    }
}   

