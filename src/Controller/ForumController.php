<?php
namespace App\Controller;

use App\Entity\Forum;
use App\Form\ForumType;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/forum')]
class ForumController extends AbstractController
{
    #[Route('/', name: 'app_forum_index', methods: ['GET'])]
    public function index(ForumRepository $forumRepository): Response
    {
        return $this->render('admin/forum/index.html.twig', [
            'forums' => $forumRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $forum = new Forum();
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($forum);
            $entityManager->flush();

            $this->addFlash('success', '✅ Forum créé avec succès !');
            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('admin/forum/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idF}', name: 'app_forum_show', methods: ['GET'])]
    public function show(Forum $forum): Response
    {
        return $this->render('admin/forum/show.html.twig', [
            'forum' => $forum,
        ]);
    }

    #[Route('/{idF}/edit', name: 'app_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Forum $forum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', '✅ Forum modifié avec succès !');
            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('admin/forum/edit.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }

    #[Route('/{idF}', name: 'app_forum_delete', methods: ['POST'])]
    public function delete(Request $request, Forum $forum, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $forum->getIdF(), $request->request->get('_token'))) {
            $entityManager->remove($forum);
            $entityManager->flush();
            $this->addFlash('success', '✅ Forum supprimé avec succès !');
        }

        return $this->redirectToRoute('app_forum_index');
    }
}