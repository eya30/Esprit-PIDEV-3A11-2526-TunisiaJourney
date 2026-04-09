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
    private const TEST_USER_ID = 1;
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg', 'avi', 'mov'];

    private function getCurrentUserId(SessionInterface $session): int
    {
        return (int) $session->get('user_id', self::TEST_USER_ID);
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

        $stats = ['like' => 0, 'dislike' => 0];
        foreach ($rows as $row) {
            $stats[$row['type']] = (int) $row['total'];
        }
        return $stats;
    }

    private function saveUploadedFile($file, SluggerInterface $slugger): ?string
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

        if ($request->isMethod('POST')) {
            $imageFile = $request->files->get('publication_image_file');
            $videoFile = $request->files->get('publication_video_file');
            $imageUrl  = trim((string) $request->request->get('image_url', ''));
            $videoUrl  = trim((string) $request->request->get('video_url', ''));

            $errors = [];

            // ✅ FIX : récupérer le nom depuis le formulaire, pas depuis l'entité (peut être null avant handleRequest)
            $nomValue = $publication->getNom();

            $titreErrors = $validator->validate($nomValue, [
                new NotBlank(['message' => 'Le titre est obligatoire.']),
                new Length(['min' => 3, 'max' => 100,
                    'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.']),
                new Regex(['pattern' => '/^[a-zA-ZÀ-ÿ\p{Arabic}]/u',
                    'message' => 'Le titre doit commencer par une lettre.']),
                new Regex(['pattern' => '/^[a-zA-ZÀ-ÿ0-9\s\'\"\-\_\.\,\?\!éèêëàâùûüîïôçœæÉÈÊËÀÂÙÛÜÎÏÔÇŒÆ]+$/u',
                    'message' => 'Le titre ne doit pas contenir de symboles spéciaux.']),
            ]);
            foreach ($titreErrors as $e) { $errors['nom'] = $e->getMessage(); }

            if ($publication->getForum() === null) {
                $errors['forum'] = 'Veuillez sélectionner un forum.';
            }

            if (!empty($imageUrl) && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $errors['media'] = "L'URL image n'est pas valide.";
            }
            if (!empty($videoUrl) && !filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                $errors['media'] = "L'URL vidéo n'est pas valide.";
            }

            if (empty($errors)) {
                if ($imageFile) {
                    $filename = $this->saveUploadedFile($imageFile, $slugger);
                    if ($filename) {
                        $publication->setImage($filename);
                    } else {
                        $errors['media'] = "Erreur lors de l'upload de l'image.";
                    }
                } elseif (!empty($imageUrl)) {
                    $publication->setImage($imageUrl);
                }

                if (empty($errors)) {
                    if ($videoFile) {
                        $filename = $this->saveUploadedFile($videoFile, $slugger);
                        if ($filename) {
                            $publication->setVideo($filename);
                        } else {
                            $errors['media'] = "Erreur lors de l'upload de la vidéo.";
                        }
                    } elseif (!empty($videoUrl)) {
                        $publication->setVideo($videoUrl);
                    }
                }

                if (empty($errors)) {
                    $publication->setId($this->getCurrentUserId($session));
                    $publication->setDateCreation(new \DateTime());
                    $publication->setVues(0);
                    $em->persist($publication);
                    $em->flush();
                    $this->addFlash('success', '✅ Publication créée avec succès !');

                    // ✅ Rediriger vers les publications du forum choisi
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

        return $this->render('admin/publication/new.html.twig', [
            'form'   => $form->createView(),
            'errors' => [],
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

        $userId   = $this->getCurrentUserId($session);
        $stats    = $this->getStats($em, $publication->getIdP());
        $userVote = $this->findVote($em, $publication->getIdP(), $userId);

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
        // ✅ Sauvegarder l'idF du forum AVANT handleRequest (au cas où le forum serait changé)
        $forumOriginal = $publication->getForum();

        $form = $this->createForm(PublicationType::class, $publication, ['is_edit' => true]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            $imageFile   = $request->files->get('publication_image_file');
            $videoFile   = $request->files->get('publication_video_file');
            $imageUrl    = trim((string) $request->request->get('image_url', ''));
            $videoUrl    = trim((string) $request->request->get('video_url', ''));
            $removeImage = $request->request->get('remove_image') === '1';
            $removeVideo = $request->request->get('remove_video') === '1';

            $errors = [];

            // ✅ FIX CRITIQUE : getNom() peut retourner null si le champ est vidé.
            // On valide la valeur brute (null sera géré par NotBlank).
            $nomValue = $publication->getNom();

            $titreErrors = $validator->validate($nomValue ?? '', [
                new NotBlank(['message' => 'Le titre est obligatoire.']),
                new Length(['min' => 3, 'max' => 100,
                    'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.']),
                new Regex(['pattern' => '/^[a-zA-ZÀ-ÿ\p{Arabic}]/u',
                    'message' => 'Le titre doit commencer par une lettre.']),
                new Regex(['pattern' => '/^[a-zA-ZÀ-ÿ0-9\s\'\"\-\_\.\,\?\!éèêëàâùûüîïôçœæÉÈÊËÀÂÙÛÜÎÏÔÇŒÆ]+$/u',
                    'message' => 'Le titre ne doit pas contenir de symboles spéciaux.']),
            ]);
            foreach ($titreErrors as $e) { $errors['nom'] = $e->getMessage(); }

            if ($publication->getForum() === null) {
                $errors['forum'] = 'Veuillez sélectionner un forum.';
            }

            if (empty($errors)) {
                if ($removeImage) {
                    $publication->setImage(null);
                }

                if ($imageFile) {
                    $filename = $this->saveUploadedFile($imageFile, $slugger);
                    if ($filename) {
                        $publication->setImage($filename);
                    } else {
                        $errors['media'] = "Erreur lors de l'upload de l'image.";
                    }
                } elseif (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $publication->setImage($imageUrl);
                }

                if ($removeVideo) {
                    $publication->setVideo(null);
                }

                if (empty($errors) && $videoFile) {
                    $filename = $this->saveUploadedFile($videoFile, $slugger);
                    if ($filename) {
                        $publication->setVideo($filename);
                    } else {
                        $errors['media'] = "Erreur lors de l'upload de la vidéo.";
                    }
                } elseif (empty($errors) && !empty($videoUrl) && filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                    $publication->setVideo($videoUrl);
                }

                if (empty($errors)) {
                    $em->flush();
                    $this->addFlash('success', '✅ Publication modifiée avec succès !');

                    // ✅ Rediriger vers les publications du forum de cette publication
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

        return $this->render('admin/publication/edit.html.twig', [
            'form'        => $form->createView(),
            'publication' => $publication,
            'errors'      => [],
        ]);
    }

    #[Route('/{idP}', name: 'app_publication_delete', methods: ['POST'])]
    public function delete(Request $request, Publication $publication, EntityManagerInterface $em): Response
    {
        $forum = $publication->getForum();
        if ($this->isCsrfTokenValid('delete' . $publication->getIdP(), $request->request->get('_token'))) {
            $em->remove($publication);
            $em->flush();
            $this->addFlash('success', '✅ Publication supprimée !');
        }
        // ✅ Retourner vers les publications du forum
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

        $existingVote    = $this->findVote($em, $publication->getIdP(), $userId);
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
        $stats = $this->getStats($em, $publication->getIdP());

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
        $userId   = $this->getCurrentUserId($session);
        $stats    = $this->getStats($em, $publication->getIdP());
        $userVote = $this->findVote($em, $publication->getIdP(), $userId);

        return new JsonResponse([
            'likes'    => $stats['like'],
            'dislikes' => $stats['dislike'],
            'userVote' => $userVote?->getType(),
        ]);
    }
}