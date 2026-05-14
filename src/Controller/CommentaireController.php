<?php
// src/Controller/CommentaireController.php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationRepository;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/commentaire')]
class CommentaireController extends AbstractController
{
    private const COMMENT_MIN_LENGTH = 3;
    private const COMMENT_MAX_LENGTH = 500;
    private const TAGS_MAX_LENGTH    = 100;

    private TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * ✅ Fix :38 & :40 — retour typé avec Constraint (classe de base importée)
     * au lieu de l'alias Assert\Constraint qui n'est pas une classe valide pour PHPStan.
     * @return array<int, Constraint>
     */
    private function getDescriptionConstraints(): array
    {
        return [
            new Assert\NotBlank(['message' => 'Le commentaire ne peut pas être vide.']),
            new Assert\Length([
                'min'        => self::COMMENT_MIN_LENGTH,
                'max'        => self::COMMENT_MAX_LENGTH,
                'minMessage' => 'Le commentaire doit contenir au moins {{ limit }} caractères.',
                'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères.',
            ]),
            new Assert\Regex([
                'pattern' => '/^[a-zA-ZÀ-ÿ\p{Arabic}\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u',
                'message' => 'Le commentaire doit commencer par une lettre ou un emoji.',
            ]),
        ];
    }

    /**
     * ✅ Fix :58 & :60 — même correction que getDescriptionConstraints()
     * @return array<int, Constraint>
     */
    private function getTagsConstraints(): array
    {
        return [
            new Assert\Length([
                'max'        => self::TAGS_MAX_LENGTH,
                'maxMessage' => 'Les tags ne peuvent pas dépasser {{ limit }} caractères.',
            ]),
            new Assert\Regex([
                'pattern' => '/^[a-zA-ZÀ-ÿ0-9\s,#\-_]*$/u',
                'message' => 'Les tags ne peuvent contenir que des lettres, chiffres, virgules et tirets.',
            ]),
        ];
    }

    #[Route('/', name: 'app_commentaire_index', methods: ['GET'])]
    public function index(CommentaireRepository $repo, PublicationRepository $pubRepo): Response
    {
        return $this->render('admin/commentaire/index.html.twig', [
            'commentaires' => $repo->findAll(),
            'publications' => $pubRepo->findAll(),
        ]);
    }

    #[Route('/new/{idP}', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ValidatorInterface $validator
    ): Response {
        if ($request->isMethod('GET')) {
            return $this->render('admin/commentaire/new.html.twig', [
                'publication' => $publication,
            ]);
        }

        $description = trim($request->request->getString('description'));
        $tags        = trim($request->request->getString('tags'));
        $errors      = [];

        // ✅ Fix :101 & :105 — passer array<int, Constraint> directement à validate()
        // puis accéder à [0] après count() > 0 (jamais null dans ce contexte)
        $descViolations = $validator->validate($description, $this->getDescriptionConstraints());
        if (count($descViolations) > 0) {
            $violation = $descViolations[0];
            if ($violation !== null) {
                $errors['description'] = $violation->getMessage();
            }
        }

        if ($tags !== '') {
            // ✅ Fix :109 & :112
            $tagsViolations = $validator->validate($tags, $this->getTagsConstraints());
            if (count($tagsViolations) > 0) {
                $violation = $tagsViolations[0];
                if ($violation !== null) {
                    $errors['tags'] = $violation->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            return $this->render('admin/commentaire/new.html.twig', [
                'publication' => $publication,
                'errors'      => $errors,
                'old'         => ['description' => $description, 'tags' => $tags],
            ]);
        }

        $commentaire = new Commentaire();
        $commentaire->setPublication($publication);
        $commentaire->setDescription($description);
        $commentaire->setTags($tags ?: '');
        $commentaire->setDateCreation(new \DateTime());
        $commentaire->setId(1);

        try {
            $em->persist($commentaire);
            $em->flush();
            $this->addFlash('success', '✅ Commentaire ajouté avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    #[Route('/api/new/{idP}', name: 'api_commentaire_new', methods: ['POST'])]
    public function apiNew(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['success' => false, 'field' => 'description', 'error' => 'Les données envoyées sont invalides.'], 400);
        }

        $description = isset($data['description']) ? trim((string) $data['description']) : '';
        $tags        = isset($data['tags'])        ? trim((string) $data['tags'])        : '';

        // ✅ Fix :163 & :167
        $descViolations = $validator->validate($description, $this->getDescriptionConstraints());
        if (count($descViolations) > 0) {
            $violation = $descViolations[0];
            if ($violation !== null) {
                return new JsonResponse(['success' => false, 'field' => 'description', 'error' => $violation->getMessage()], 422);
            }
        }

        if ($tags !== '') {
            // ✅ Fix :171 & :174
            $tagsViolations = $validator->validate($tags, $this->getTagsConstraints());
            if (count($tagsViolations) > 0) {
                $violation = $tagsViolations[0];
                if ($violation !== null) {
                    return new JsonResponse(['success' => false, 'field' => 'tags', 'error' => $violation->getMessage()], 422);
                }
            }
        }

        $commentaire = new Commentaire();
        $commentaire->setPublication($publication);
        $commentaire->setDescription($description);
        $commentaire->setTags($tags);
        $commentaire->setDateCreation(new \DateTime());
        $commentaire->setId(1);

        try {
            $em->persist($commentaire);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'field' => null, 'error' => 'Erreur de sauvegarde en base de données.'], 500);
        }

        if ($commentaire->getIdC() === null) {
            return new JsonResponse(['success' => false, 'field' => null, 'error' => 'Le commentaire n\'a pas été sauvegardé.'], 500);
        }

        $em->refresh($publication);
        $total        = count($publication->getCommentaires());
        $dateCreation = $commentaire->getDateCreation();

        return new JsonResponse([
            'success' => true,
            'comment' => [
                'id'          => $commentaire->getIdC(),
                'description' => $commentaire->getDescription(),
                'tags'        => $commentaire->getTags() ?? '',
                'date'        => $dateCreation !== null ? $dateCreation->format('d/m/Y H:i') : '',
            ],
            'total' => $total,
        ]);
    }

    #[Route('/api/{idC}', name: 'api_commentaire_delete', methods: ['DELETE'])]
    public function apiDelete(Commentaire $commentaire, EntityManagerInterface $em): JsonResponse
    {
        $publication = $commentaire->getPublication();
        try {
            $em->remove($commentaire);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
        }

        if ($publication === null) {
            return new JsonResponse(['success' => true, 'total' => 0]);
        }

        $em->refresh($publication);
        return new JsonResponse(['success' => true, 'total' => count($publication->getCommentaires())]);
    }

    #[Route('/api/{idC}/edit', name: 'api_commentaire_edit', methods: ['PUT'])]
    public function apiEdit(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['success' => false, 'field' => 'description', 'error' => 'Les données envoyées sont invalides.'], 400);
        }

        $description = isset($data['description']) ? trim((string) $data['description']) : '';
        $tags        = isset($data['tags'])        ? trim((string) $data['tags'])        : null;

        // ✅ Fix :265 — passer array<int, Constraint> et guard null sur violation[0]
        $descViolations = $validator->validate($description, [
            new Assert\NotBlank(['message' => 'Le commentaire ne peut pas être vide.']),
            new Assert\Length([
                'min'        => 1,
                'max'        => self::COMMENT_MAX_LENGTH,
                'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères.',
            ]),
        ]);
        if (count($descViolations) > 0) {
            $violation = $descViolations[0];
            if ($violation !== null) {
                return new JsonResponse(['success' => false, 'field' => 'description', 'error' => $violation->getMessage()], 422);
            }
        }

        if ($tags !== null && $tags !== '') {
            // ✅ Fix :269 & :272
            $tagsViolations = $validator->validate($tags, $this->getTagsConstraints());
            if (count($tagsViolations) > 0) {
                $violation = $tagsViolations[0];
                if ($violation !== null) {
                    return new JsonResponse(['success' => false, 'field' => 'tags', 'error' => $violation->getMessage()], 422);
                }
            }
        }

        $commentaire->setDescription($description);
        if ($tags !== null) {
            $commentaire->setTags($tags);
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'field' => null, 'error' => 'Erreur lors de la modification : ' . $e->getMessage()], 500);
        }

        return new JsonResponse([
            'success'     => true,
            'description' => $commentaire->getDescription(),
            'tags'        => $commentaire->getTags() ?? '',
        ]);
    }

    #[Route('/publication/{idP}/comments', name: 'api_publication_comments', methods: ['GET'])]
    public function getPublicationComments(Publication $publication, CommentaireRepository $commentaireRepo): JsonResponse
    {
        /** @var Commentaire[] $allComments */
        $allComments = $commentaireRepo->findBy(['publication' => $publication], ['dateCreation' => 'DESC']);

        $comments = [];
        foreach ($allComments as $comment) {
            if ($comment->getIsCancelled()) {
                continue;
            }
            $dateCreation = $comment->getDateCreation();
            $comments[]   = [
                'id'          => $comment->getIdC(),
                'description' => $comment->getDescription(),
                'tags'        => $comment->getTags() ?? '',
                'date'        => $dateCreation !== null ? $dateCreation->format('d/m/Y') : '',
                'likes'       => 0,
            ];
        }

        return new JsonResponse(['success' => true, 'comments' => $comments, 'total' => count($comments)]);
    }

    #[Route('/{idC}/cancel', name: 'app_commentaire_cancel', methods: ['POST'])]
    public function cancel(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('cancel' . $commentaire->getIdC(), $token)) {
            $commentaire->setIsCancelled(true);
            $commentaire->setCancelledAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', '✅ Commentaire bloqué avec succès !');
        } else {
            $this->addFlash('error', '❌ Token CSRF invalide.');
        }

        $publication = $commentaire->getPublication();
        if ($publication === null) {
            return $this->redirectToRoute('app_commentaire_index');
        }
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    #[Route('/{idC}/uncancel', name: 'app_commentaire_uncancel', methods: ['POST'])]
    public function uncancel(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('uncancel' . $commentaire->getIdC(), $token)) {
            $commentaire->setIsCancelled(false);
            $commentaire->setCancelledAt(null);
            $em->flush();
            $this->addFlash('success', '✅ Commentaire débloqué avec succès !');
        } else {
            $this->addFlash('error', '❌ Token CSRF invalide.');
        }

        $publication = $commentaire->getPublication();
        if ($publication === null) {
            return $this->redirectToRoute('app_commentaire_index');
        }
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    #[Route('/{idC}/show', name: 'app_commentaire_show', methods: ['GET'])]
    public function show(Commentaire $commentaire): Response
    {
        return $this->render('admin/commentaire/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/{idC}', name: 'app_commentaire_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $publication = $commentaire->getPublication();
        $token       = $request->request->getString('_token');

        if ($this->isCsrfTokenValid('delete' . $commentaire->getIdC(), $token)) {
            try {
                $em->remove($commentaire);
                $em->flush();
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
                }
                $this->addFlash('error', '❌ Erreur : ' . $e->getMessage());
                if ($publication === null) {
                    return $this->redirectToRoute('app_commentaire_index');
                }
                return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
            }

            if ($request->isXmlHttpRequest()) {
                if ($publication !== null) {
                    $em->refresh($publication);
                    return new JsonResponse(['success' => true, 'total' => count($publication->getCommentaires())]);
                }
                return new JsonResponse(['success' => true, 'total' => 0]);
            }

            $this->addFlash('success', 'Commentaire supprimé !');
        }

        if ($publication === null) {
            return $this->redirectToRoute('app_commentaire_index');
        }
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // API DE TRADUCTION POUR COMMENTAIRES
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/api/translate/{idC}', name: 'api_commentaire_translate', methods: ['POST'])]
    public function translateComment(
        int $idC,
        Request $request,
        CommentaireRepository $commentaireRepository
    ): JsonResponse {
        /** @var Commentaire|null $commentaire */
        $commentaire = $commentaireRepository->find($idC);
        if (!$commentaire instanceof Commentaire) {
            return new JsonResponse(['success' => false, 'error' => 'Commentaire introuvable.'], 404);
        }

        $data       = json_decode($request->getContent(), true);
        $targetLang = is_array($data) ? (string) ($data['targetLang'] ?? 'en') : 'en';
        $sourceLang = is_array($data) ? ($data['sourceLang'] ?? null) : null;

        if (!$this->translationService->isLanguageSupported($targetLang)) {
            return new JsonResponse(['success' => false, 'error' => 'Langue non supportée.'], 422);
        }

        // ✅ Fix :441 & :442 — getDescription() retourne ?string, on guard avant de passer à translate()
        $originalText = $commentaire->getDescription() ?? '';

        $translatedText = $this->translationService->translate($originalText, $targetLang, $sourceLang);
        $detectedLang   = $this->translationService->detectLanguage($originalText);

        return new JsonResponse([
            'success'      => true,
            'original'     => $originalText,
            'translated'   => $translatedText,
            'targetLang'   => $targetLang,
            'detectedLang' => $detectedLang,
        ]);
    }

    #[Route('/api/replace/{idC}', name: 'api_commentaire_replace', methods: ['PUT'])]
    public function replaceCommentWithTranslation(
        int $idC,
        Request $request,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Commentaire|null $commentaire */
        $commentaire = $commentaireRepository->find($idC);
        if (!$commentaire instanceof Commentaire) {
            return new JsonResponse(['success' => false, 'error' => 'Commentaire introuvable.'], 404);
        }

        $data       = json_decode($request->getContent(), true);
        $newText    = is_array($data) ? trim((string) ($data['newText']    ?? '')) : '';
        $targetLang = is_array($data) ? (string)       ($data['targetLang'] ?? 'en') : 'en';

        if (empty($newText)) {
            return new JsonResponse(['success' => false, 'error' => 'Le texte traduit est vide.'], 422);
        }

        if (!$commentaire->getOriginalDescription()) {
            $commentaire->setOriginalDescription($commentaire->getDescription());
        }

        $commentaire->setDescription($newText);
        $commentaire->setTranslatedLang($targetLang);
        $commentaire->setIsTranslated(true);

        try {
            $em->flush();
            return new JsonResponse([
                'success'      => true,
                'message'      => 'Commentaire remplacé par la traduction !',
                'newText'      => $newText,
                'originalText' => $commentaire->getOriginalDescription(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors du remplacement: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/restore/{idC}', name: 'api_commentaire_restore', methods: ['PUT'])]
    public function restoreOriginalComment(
        int $idC,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Commentaire|null $commentaire */
        $commentaire = $commentaireRepository->find($idC);
        if (!$commentaire instanceof Commentaire) {
            return new JsonResponse(['success' => false, 'error' => 'Commentaire introuvable.'], 404);
        }

        if (!$commentaire->getIsTranslated() || !$commentaire->getOriginalDescription()) {
            return new JsonResponse(['success' => false, 'error' => 'Aucun original à restaurer.'], 422);
        }

        $commentaire->setDescription($commentaire->getOriginalDescription());
        $commentaire->setOriginalDescription(null);
        $commentaire->setTranslatedLang(null);
        $commentaire->setIsTranslated(false);

        try {
            $em->flush();
            return new JsonResponse([
                'success'      => true,
                'message'      => 'Commentaire original restauré !',
                'originalText' => $commentaire->getDescription(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de la restauration: ' . $e->getMessage()], 500);
        }
    }
}