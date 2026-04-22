<?php
namespace App\Controller;

use App\Repository\ForumRepository;
use App\Repository\PublicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    // ── PAGE D'ACCUEIL (hôtels, destinations, culture...) ──
    #[Route('/accueil', name: 'app_accueil')]
    public function index(
        ForumRepository $forumRepo,
        PublicationRepository $pubRepo
    ): Response {
        return $this->render('accueil/index.html.twig', [
            'forums'       => $forumRepo->findAll(),
            'publications' => $pubRepo->findAll(),
        ]);
    }

    // ── PAGE FORUM PUBLIC (publications + commentaires) ──
    #[Route('/forum-voyageurs', name: 'app_forum_public')]
    public function forumPublic(
        ForumRepository $forumRepo,
        PublicationRepository $pubRepo
    ): Response {
        return $this->render('Forum/forum_public.html.twig', [
            'forums'       => $forumRepo->findAll(),
            'publications' => $pubRepo->findAll(),
        ]);
    }
}