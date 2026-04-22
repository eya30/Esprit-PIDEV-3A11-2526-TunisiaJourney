<?php
// src/Controller/ForumPublicController.php

namespace App\Controller;

use App\Repository\PublicationRepository;
use App\Repository\ForumRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForumPublicController extends AbstractController
{
    // ✅ SEUL l'admin (id=1) crée les publications visibles dans le forum public
    // Les publications des utilisateurs (id > 1) apparaissent UNIQUEMENT dans leur profil
    private const ADMIN_USER_ID = 1;

    // Nombre de publications par page (modifiez selon vos besoins)
    private const ITEMS_PER_PAGE = 6;

    #[Route('/forum-voyageurs', name: 'app_forum_public')]
    public function index(
        Request               $request,
        PublicationRepository $publicationRepository,
        ForumRepository       $forumRepository,
        PaginatorInterface    $paginator,
        SessionInterface      $session
    ): Response {
        // ─── Paramètres GET ────────────────────────────────────────────
        $page         = max(1, (int) $request->query->get('page', 1));
        $tab          = $request->query->get('tab', 'video');   // 'video' | 'image'
        $videoPage    = max(1, (int) $request->query->get('videoPage', 1));
        $imagePage    = max(1, (int) $request->query->get('imagePage', 1));

        // ─── Query Builder — publications admin uniquement ──────────────
        $qbVideo = $publicationRepository->createQueryBuilder('p')
            ->where('p.id = :adminId')
            ->andWhere('p.video IS NOT NULL')
            ->andWhere("p.video != ''")
            ->setParameter('adminId', self::ADMIN_USER_ID)
            ->orderBy('p.dateCreation', 'DESC');

        $qbImage = $publicationRepository->createQueryBuilder('p')
            ->where('p.id = :adminId')
            ->andWhere('p.image IS NOT NULL')
            ->andWhere("p.image != ''")
            ->setParameter('adminId', self::ADMIN_USER_ID)
            ->orderBy('p.dateCreation', 'DESC');

        // ─── Pagination avec KnpPaginator ───────────────────────────────
        $paginationVideo = $paginator->paginate(
            $qbVideo,
            $videoPage,
            self::ITEMS_PER_PAGE,
            [
                'pageParameterName'  => 'videoPage',
                'sortFieldWhitelist' => ['p.dateCreation', 'p.nom'],
            ]
        );

        $paginationImage = $paginator->paginate(
            $qbImage,
            $imagePage,
            self::ITEMS_PER_PAGE,
            [
                'pageParameterName'  => 'imagePage',
                'sortFieldWhitelist' => ['p.dateCreation', 'p.nom'],
            ]
        );

        // ─── Totaux pour les badges d'onglets ───────────────────────────
        $totalVideo = $paginationVideo->getTotalItemCount();
        $totalImage = $paginationImage->getTotalItemCount();

        // ─── Toutes les publications pour les stats héro ─────────────────
        // (on compte séparément pour éviter une requête inutile)
        $totalPublications = $totalVideo + $totalImage;

        // ─── Total commentaires (pour le stat héro) ─────────────────────
        $totalComments = (int) $publicationRepository->createQueryBuilder('p')
            ->select('SUM(SIZE(p.commentaires))')
            ->where('p.id = :adminId')
            ->setParameter('adminId', self::ADMIN_USER_ID)
            ->getQuery()
            ->getSingleScalarResult();

        // ─── ID visiteur courant ─────────────────────────────────────────
        $currentUserId = (int) $session->get('user_id', self::ADMIN_USER_ID);

        return $this->render('Forum/forum_public.html.twig', [
            'paginationVideo'   => $paginationVideo,
            'paginationImage'   => $paginationImage,
            'totalVideo'        => $totalVideo,
            'totalImage'        => $totalImage,
            'totalPublications' => $totalPublications,
            'totalComments'     => $totalComments,
            'forums'            => $forumRepository->findAll(),
            'currentUserId'     => $currentUserId,
            'activeTab'         => $tab,
            'videoPage'         => $videoPage,
            'imagePage'         => $imagePage,
            'itemsPerPage'      => self::ITEMS_PER_PAGE,
        ]);
    }
}