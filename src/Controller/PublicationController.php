<?php

namespace App\Controller;

use App\Entity\LikePublication;
use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

#[Route('/publication')]
class PublicationController extends AbstractController
{
    private const ADMIN_USER_ID = 1;

    private function getOrCreateVisitorId(SessionInterface $session): int
    {
        $visitorId = (int) $session->get('visitor_id', 0);
        if ($visitorId < 10000) {
            $visitorId = rand(10000, 999999);
            $session->set('visitor_id', $visitorId);
        }
        return $visitorId;
    }

    private function getCurrentUserId(SessionInterface $session): int
    {
        return (int) $session->get('user_id', self::ADMIN_USER_ID);
    }

    private function findVote(EntityManagerInterface $em, int $pubId, int $userId): ?LikePublication
    {
        return $em->createQuery(
            'SELECT l FROM App\Entity\LikePublication l
             WHERE IDENTITY(l.publication) = :pubId AND l.userId = :userId'
        )
        ->setParameter('pubId', $pubId)
        ->setParameter('userId', $userId)
        ->getOneOrNullResult();
    }

    /**
     * @return array{like: int, dislike: int}
     */
    private function getStats(EntityManagerInterface $em, int $pubId): array
    {
        $rows = $em->createQuery(
            'SELECT l.type, COUNT(l.id) AS total
             FROM App\Entity\LikePublication l
             WHERE IDENTITY(l.publication) = :pubId
             GROUP BY l.type'
        )
        ->setParameter('pubId', $pubId)
        ->getResult();

        // Fix ligne 75 : on déclare les clés explicitement pour satisfaire array{like: int, dislike: int}
        $like    = 0;
        $dislike = 0;

        foreach ($rows as $row) {
            if ($row['type'] === 'like') {
                $like = (int) $row['total'];
            } elseif ($row['type'] === 'dislike') {
                $dislike = (int) $row['total'];
            }
        }

        return ['like' => $like, 'dislike' => $dislike];
    }

    /**
     * @return array{like: int, love: int, haha: int, wow: int, sad: int, angry: int}
     */
    private function getReactionStats(EntityManagerInterface $em, int $pubId): array
    {
        $rows = $em->createQuery(
            'SELECT l.type, COUNT(l.id) AS total
             FROM App\Entity\LikePublication l
             WHERE IDENTITY(l.publication) = :pubId
             GROUP BY l.type'
        )
        ->setParameter('pubId', $pubId)
        ->getResult();

        $like  = 0;
        $love  = 0;
        $haha  = 0;
        $wow   = 0;
        $sad   = 0;
        $angry = 0;

        foreach ($rows as $row) {
            match ($row['type']) {
                'like'  => $like  = (int) $row['total'],
                'love'  => $love  = (int) $row['total'],
                'haha'  => $haha  = (int) $row['total'],
                'wow'   => $wow   = (int) $row['total'],
                'sad'   => $sad   = (int) $row['total'],
                'angry' => $angry = (int) $row['total'],
                default => null,
            };
        }

        return [
            'like'  => $like,
            'love'  => $love,
            'haha'  => $haha,
            'wow'   => $wow,
            'sad'   => $sad,
            'angry' => $angry,
        ];
    }

    private function saveUploadedFile(mixed $file, SluggerInterface $slugger): ?string
    {
        if (!$file) return null;
        $newFilename = $slugger->slug(
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
        ) . '-' . uniqid() . '.' . $file->guessExtension();
        try {
            $file->move($this->getParameter('publications_directory'), $newFilename);
            return $newFilename;
        } catch (FileException) {
            return null;
        }
    }

    #[Route('/', name: 'app_publication_index', methods: ['GET'])]
    public function index(PublicationRepository $repo): Response
    {
        return $this->render('admin/publication/index.html.twig', [
            'publications' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_publication_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        SessionInterface $session,
        ValidatorInterface $validator
    ): Response {
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication, ['is_edit' => false]);
        $form->handleRequest($request);
        $errors = [];

        if ($request->isMethod('POST')) {
            $imageFile = $request->files->get('publication_image_file');
            $videoFile = $request->files->get('publication_video_file');

            $imageUrl = trim($request->request->getString('image_url'));
            $videoUrl = trim($request->request->getString('video_url'));

            $nomValue = $publication->getNom();

            $titreErrors = $validator->validate($nomValue, [
                new NotBlank(['message' => 'Le titre est obligatoire.']),
                new Length([
                    'min' => 3, 'max' => 100,
                    'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                ]),
                new Regex([
                    'pattern' => '/^[a-zA-ZÀ-ÿ\p{Arabic}]/u',
                    'message' => 'Le titre doit commencer par une lettre.',
                ]),
            ]);
            foreach ($titreErrors as $e) {
                $errors['nom'] = $e->getMessage();
            }
            if ($publication->getForum() === null) {
                $errors['forum'] = 'Veuillez sélectionner un forum.';
            }

            if (empty($errors)) {
                if ($imageFile) {
                    $filename = $this->saveUploadedFile($imageFile, $slugger);
                    if ($filename) $publication->setImage($filename);
                } elseif (!empty($imageUrl)) {
                    $publication->setImage($imageUrl);
                }
                if ($videoFile) {
                    $filename = $this->saveUploadedFile($videoFile, $slugger);
                    if ($filename) $publication->setVideo($filename);
                } elseif (!empty($videoUrl)) {
                    $publication->setVideo($videoUrl);
                }

                $publication->setId(self::ADMIN_USER_ID);
                $publication->setDateCreation(new \DateTime());
                $publication->setVues(0);
                $em->persist($publication);
                $em->flush();
                $this->addFlash('success', '✅ Publication créée avec succès !');

                $forum = $publication->getForum();
                if ($forum !== null) {
                    return $this->redirectToRoute('app_forum_show', ['idF' => $forum->getIdF()]);
                }
                return $this->redirectToRoute('app_publication_index');
            }
        }

        return $this->render('admin/publication/new.html.twig', [
            'form'   => $form->createView(),
            'errors' => $errors,
        ]);
    }

    #[Route('/api/new', name: 'api_publication_new', methods: ['POST'])]
    public function apiNew(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        SessionInterface $session,
        ValidatorInterface $validator
    ): JsonResponse {
        $title       = trim($request->request->getString('title'));
        $description = trim($request->request->getString('description'));
        $forumId     = $request->request->getInt('forumId');
        $videoUrl    = trim($request->request->getString('videoUrl'));

        if (empty($title) || mb_strlen($title) < 3) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Le titre doit contenir au moins 3 caractères.'
            ], 422);
        }
        if ($forumId <= 0) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Veuillez sélectionner un forum.'
            ], 422);
        }

        $forum = $em->find(\App\Entity\Forum::class, $forumId);
        if (!$forum) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Forum introuvable.'
            ], 404);
        }

        $visitorId = $this->getOrCreateVisitorId($session);

        $publication = new Publication();
        $publication->setNom($title);
        $publication->setDescription($description ?: null);
        $publication->setForum($forum);
        $publication->setId($visitorId);
        $publication->setDateCreation(new \DateTime());
        $publication->setVues(0);

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $filename = $this->saveUploadedFile($imageFile, $slugger);
            if ($filename) $publication->setImage($filename);
        }

        $videoFile = $request->files->get('video');
        if ($videoFile) {
            $filename = $this->saveUploadedFile($videoFile, $slugger);
            if ($filename) $publication->setVideo($filename);
        } elseif (!empty($videoUrl) && filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            $publication->setVideo($videoUrl);
        }

        try {
            $em->persist($publication);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Erreur lors de la sauvegarde.'
            ], 500);
        }

        $countUser = (int) $em->createQuery(
            'SELECT COUNT(p.idP) FROM App\Entity\Publication p WHERE p.id = :userId'
        )->setParameter('userId', $visitorId)->getSingleScalarResult();

        $profileJustCreated = ($countUser === 1);
        if ($profileJustCreated) {
            $session->set('profile_just_created_' . $visitorId, true);
        }

        $profileUrl = $this->generateUrl('app_user_profile', ['userId' => $visitorId]);

        return new JsonResponse([
            'success'            => true,
            'message'            => 'Votre aventure a été publiée dans votre profil !',
            'publicationId'      => $publication->getIdP(),
            'userId'             => $visitorId,
            'profileUrl'         => $profileUrl,
            'profileJustCreated' => $profileJustCreated,
        ]);
    }

    #[Route('/{idP}', name: 'app_publication_show', methods: ['GET'])]
    public function show(Publication $publication): Response
    {
        return $this->render('admin/publication/show.html.twig', [
            'publication' => $publication,
        ]);
    }

    #[Route('/video/{idP}/watch', name: 'app_publication_watch', methods: ['GET'])]
    public function watch(
        Publication $publication,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        $publication->setVues($publication->getVues() + 1);
        $em->flush();

        $userId = $this->getCurrentUserId($session);
        $pubId  = $publication->getIdP();

        if ($pubId === null) {
            throw $this->createNotFoundException('Publication sans identifiant.');
        }

        $stats    = $this->getStats($em, $pubId);
        $userVote = $this->findVote($em, $pubId, $userId);

        return $this->render('Forum/watch.html.twig', [
            'publication' => $publication,
            'likes'       => $stats['like'],
            'dislikes'    => $stats['dislike'],
            'userVote'    => $userVote?->getType(),
        ]);
    }

    #[Route('/{idP}/edit', name: 'app_publication_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ValidatorInterface $validator
    ): Response {
        $forumOriginal = $publication->getForum();
        $form = $this->createForm(PublicationType::class, $publication, ['is_edit' => true]);
        $errors = [];

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $imageFile = $request->files->get('publication_image_file');
            $videoFile = $request->files->get('publication_video_file');

            $imageUrl    = trim($request->request->getString('image_url'));
            $videoUrl    = trim($request->request->getString('video_url'));
            $removeImage = $request->request->getString('remove_image') === '1';
            $removeVideo = $request->request->getString('remove_video') === '1';

            $titreErrors = $validator->validate($publication->getNom() ?? '', [
                new NotBlank(['message' => 'Le titre est obligatoire.']),
                new Length([
                    'min' => 3, 'max' => 100,
                    'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                ]),
            ]);
            foreach ($titreErrors as $e) {
                $errors['nom'] = $e->getMessage();
            }
            if ($publication->getForum() === null) {
                $errors['forum'] = 'Veuillez sélectionner un forum.';
            }

            if (empty($errors)) {
                if ($removeImage) $publication->setImage(null);
                if ($imageFile) {
                    $filename = $this->saveUploadedFile($imageFile, $slugger);
                    if ($filename) $publication->setImage($filename);
                } elseif (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $publication->setImage($imageUrl);
                }
                if ($removeVideo) $publication->setVideo(null);
                if ($videoFile) {
                    $filename = $this->saveUploadedFile($videoFile, $slugger);
                    if ($filename) $publication->setVideo($filename);
                } elseif (!empty($videoUrl) && filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                    $publication->setVideo($videoUrl);
                }

                $em->flush();
                $this->addFlash('success', '✅ Publication modifiée avec succès !');
                $forum = $publication->getForum() ?? $forumOriginal;
                if ($forum !== null) {
                    return $this->redirectToRoute('app_forum_show', ['idF' => $forum->getIdF()]);
                }
                return $this->redirectToRoute('app_publication_index');
            }
        }

        return $this->render('admin/publication/edit.html.twig', [
            'form'        => $form->createView(),
            'publication' => $publication,
            'errors'      => $errors,
        ]);
    }

    #[Route('/{idP}', name: 'app_publication_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em
    ): Response {
        $forum = $publication->getForum();
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $publication->getIdP(), is_string($token) ? $token : null)) {
            $em->remove($publication);
            $em->flush();
            $this->addFlash('success', '✅ Publication supprimée !');
        }

        if ($forum !== null) {
            return $this->redirectToRoute('app_forum_show', ['idF' => $forum->getIdF()]);
        }
        return $this->redirectToRoute('app_publication_index');
    }

    #[Route('/{idP}/vote', name: 'app_publication_vote', methods: ['POST'])]
    public function vote(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        $userId   = $this->getCurrentUserId($session);
        $typeVote = $request->request->get('type');

        if (!in_array($typeVote, ['like', 'dislike'], true)) {
            return new JsonResponse(['success' => false, 'error' => 'Type invalide'], 400);
        }

        $pubId = $publication->getIdP();
        if ($pubId === null) {
            return new JsonResponse(['success' => false, 'error' => 'Publication invalide'], 500);
        }

        $existingVote    = $this->findVote($em, $pubId, $userId);
        $userCurrentVote = null;

        if ($existingVote !== null) {
            if ($existingVote->getType() === $typeVote) {
                $em->remove($existingVote);
            } else {
                $existingVote->setType($typeVote);
                $userCurrentVote = $typeVote;
            }
        } else {
            $newVote = new LikePublication();
            $newVote->setType($typeVote);
            $newVote->setPublication($publication);
            $newVote->setUserId($userId);
            $newVote->setDateAction(new \DateTime());
            $em->persist($newVote);
            $userCurrentVote = $typeVote;
        }

        $em->flush();
        $stats = $this->getStats($em, $pubId);

        return new JsonResponse([
            'success'  => true,
            'likes'    => $stats['like'],
            'dislikes' => $stats['dislike'],
            'userVote' => $userCurrentVote,
        ]);
    }

    #[Route('/{idP}/likes', name: 'app_publication_likes', methods: ['GET'])]
    public function getLikes(
        Publication $publication,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        $userId = $this->getCurrentUserId($session);

        $pubId = $publication->getIdP();
        if ($pubId === null) {
            return new JsonResponse(['success' => false, 'error' => 'Publication invalide'], 500);
        }

        $stats    = $this->getStats($em, $pubId);
        $userVote = $this->findVote($em, $pubId, $userId);

        return new JsonResponse([
            'likes'    => $stats['like'],
            'dislikes' => $stats['dislike'],
            'userVote' => $userVote?->getType(),
        ]);
    }

    #[Route('/{idP}/react', name: 'app_publication_react', methods: ['POST'])]
    public function react(
        int $idP,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $type = is_array($data) && isset($data['type']) && is_string($data['type'])
            ? $data['type']
            : null;

        $validTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

        if ($type !== null && !in_array($type, $validTypes, true)) {
            return new JsonResponse(['success' => false, 'error' => 'Type de réaction invalide'], 400);
        }

        $publication = $em->getRepository(Publication::class)->find($idP);
        if (!$publication) {
            return new JsonResponse(['success' => false, 'error' => 'Publication non trouvée'], 404);
        }

        $userId = $this->getOrCreateVisitorId($session);

        $existingReaction = $em->createQuery(
            'SELECT l FROM App\Entity\LikePublication l
             WHERE IDENTITY(l.publication) = :pubId AND l.userId = :userId'
        )
        ->setParameter('pubId', $idP)
        ->setParameter('userId', $userId)
        ->getOneOrNullResult();

        if ($type === null) {
            if ($existingReaction) {
                $em->remove($existingReaction);
                $em->flush();
            }
        } else {
            if ($existingReaction) {
                if ($existingReaction->getType() === $type) {
                    $em->remove($existingReaction);
                } else {
                    $existingReaction->setType($type);
                    $existingReaction->setDateAction(new \DateTime());
                }
            } else {
                $newReaction = new LikePublication();
                $newReaction->setType($type);
                $newReaction->setPublication($publication);
                $newReaction->setUserId($userId);
                $newReaction->setDateAction(new \DateTime());
                $em->persist($newReaction);
            }
            $em->flush();
        }

        $stats = $this->getReactionStats($em, $idP);
        $total = array_sum($stats);

        return new JsonResponse([
            'success' => true,
            'type'    => $type,
            'stats'   => $stats,
            'total'   => $total,
        ]);
    }

    #[Route('/profil/{userId}', name: 'app_user_profile', methods: ['GET'])]
    public function userProfile(
        int $userId,
        PublicationRepository $pubRepo,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        if ($userId <= 0) {
            throw $this->createNotFoundException('Profil introuvable.');
        }

        $currentVisitorId = $this->getOrCreateVisitorId($session);
        $isOwner          = ($currentVisitorId === $userId);

        $publications = $pubRepo->createQueryBuilder('p')
            ->where('p.id = :userId')
            ->andWhere('p.id != :adminId')
            ->setParameter('userId', $userId)
            ->setParameter('adminId', self::ADMIN_USER_ID)
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        $profileJustCreated = $session->get('profile_just_created_' . $userId, false);
        if ($profileJustCreated) {
            $session->remove('profile_just_created_' . $userId);
        }

        $forums = $em->getRepository(\App\Entity\Forum::class)->findBy(['status' => 'actif']);

        return $this->render('Forum/user_profile.html.twig', [
            'userId'             => $userId,
            'publications'       => $publications,
            'isOwner'            => $isOwner,
            'profileJustCreated' => $profileJustCreated,
            'currentUserId'      => $currentVisitorId,
            'forums'             => $forums,
        ]);
    }
}