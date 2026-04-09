<?php

namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Voyage;
use App\Form\ProgrammeType;
use App\Repository\ProgrammeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/programme')]
class ProgrammeController extends AbstractController
{
    #[Route('/', name: 'app_programme_index', methods: ['GET'])]
    public function index(ProgrammeRepository $programmeRepository): Response
    {
        return $this->render('programme/index.html.twig', [
            'programmes' => $programmeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_programme_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $programme = new Programme();
        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $programme->setIdProg('PRG_' . uniqid());
            
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('programmes_images_directory'), $newFilename);
                $programme->setImage($newFilename);
            }
            
            $entityManager->persist($programme);
            $entityManager->flush();

            $this->addFlash('success', 'Programme créé avec succès !');
            return $this->redirectToRoute('app_programme_index');
        }

        return $this->render('programme/new.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }

    #[Route('/{idProg}', name: 'app_programme_show', methods: ['GET'])]
    public function show(Programme $programme): Response
    {
        return $this->render('programme/show.html.twig', [
            'programme' => $programme,
            'old' => []
        ]);
    }

    // ✅ CORRECTION : Version ultra-simple compatible Symfony 7+
    #[Route('/{idProg}/with-data', name: 'app_programme_show_with_data', methods: ['GET'])]
    public function showWithData(Programme $programme, Request $request): Response
    {
        // 🟢 Les messages flash (error/success) sont automatiquement disponibles 
        // dans Twig via app.flashes() après un redirect. Aucun besoin de getFlashBag() !
        
        return $this->render('programme/show.html.twig', [
            'programme' => $programme,
            'old' => [
                'nom' => $request->query->get('nom', ''),
                'prenom' => $request->query->get('prenom', ''),
                'telephone' => $request->query->get('telephone', ''),
                'email' => $request->query->get('email', ''),
                'nbre' => $request->query->get('nbre', ''),
            ]
        ]);
    }

    #[Route('/{idProg}/edit', name: 'app_programme_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Programme $programme, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('programmes_images_directory'), $newFilename);
                $programme->setImage($newFilename);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Programme modifié avec succès !');
            return $this->redirectToRoute('app_programme_index');
        }

        return $this->render('programme/edit.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }

    #[Route('/{idProg}', name: 'app_programme_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Programme $programme, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $programme->getIdProg(), $request->request->get('_token'))) {
            $entityManager->remove($programme);
            $entityManager->flush();
            $this->addFlash('success', 'Programme supprimé avec succès !');
        }

        return $this->redirectToRoute('app_programme_index');
    }
    
    #[Route('/voyage/{idV}', name: 'app_programme_by_voyage', methods: ['GET'])]
    public function programmesByVoyage(Voyage $voyage, ProgrammeRepository $programmeRepository): Response
    {
        return $this->render('programme/by_voyage.html.twig', [
            'voyage' => $voyage,
            'programmes' => $programmeRepository->findBy(['voyage' => $voyage]),
        ]);
    }
}