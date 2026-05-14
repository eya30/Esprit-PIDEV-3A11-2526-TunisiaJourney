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
use Knp\Component\Pager\PaginatorInterface;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    // ✅ Liste avec pagination
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProduitRepository $repo,
        PaginatorInterface $paginator
    ): Response {
        $search = $request->query->get('q', '');
        $cat    = $request->query->get('cat', '');

        // Requête de base
        $qb = $repo->createQueryBuilder('p')
            ->orderBy('p.idPR', 'DESC');

        if ($search) {
            $qb->andWhere('p.titre LIKE :q OR p.description LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($cat) {
            $qb->andWhere('p.categorie = :cat')
               ->setParameter('cat', $cat);
        }

        // ✅ Pagination — 6 produits par page
        $produits = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'search'   => $search,
            'cat'      => $cat,
        ]);
    }

    // Nouveau produit
    #[Route('/nouveau', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

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

    // Modifier
    #[Route('/{idPR}/modifier', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Produit $produit, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

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

    // Supprimer
<<<<<<< HEAD
   #[Route('/{idPR}/supprimer', name: 'app_produit_delete', methods: ['POST'])]
public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('delete' . $produit->getIdPR(), $request->request->getString('_token'))) {
        $em->remove($produit);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé.');
=======
    #[Route('/{idPR}/supprimer', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $produit->getIdPR(), $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }

        return $this->redirectToRoute('app_produit_index');
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }

    return $this->redirectToRoute('app_produit_index');
}
}