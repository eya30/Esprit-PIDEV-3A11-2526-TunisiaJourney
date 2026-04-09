<?php

namespace App\Controller;

use App\Repository\PublicationRepository;
use App\Repository\ForumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForumPublicController extends AbstractController
{
    #[Route('/forum-voyageurs', name: 'app_forum_public')]
    public function index(
        PublicationRepository $publicationRepository,
        ForumRepository $forumRepository
    ): Response {
        return $this->render('Forum/forum_public.html.twig', [
            'publications' => $publicationRepository->findAll(),
            'forums'       => $forumRepository->findAll(),
        ]);
    }
}