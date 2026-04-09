<?php
namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/commentaire')]
class CommentaireController extends AbstractController
{
    private const COMMENT_MIN_LENGTH = 3;
    private const COMMENT_MAX_LENGTH = 500;
    private const TAGS_MAX_LENGTH    = 100;

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

    // ══════════════════════════════════════════════════════════════════════
    // INDEX (Back-office)
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/', name: 'app_commentaire_index', methods: ['GET'])]
    public function index(CommentaireRepository $repo, PublicationRepository $pubRepo): Response
    {
        return $this->render('admin/commentaire/index.html.twig', [
            'commentaires' => $repo->findAll(),
            'publications' => $pubRepo->findAll(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // NEW (Back-office) — redirige vers les commentaires de la publication
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/new/{idP}', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ValidatorInterface $validator
    ): Response {
        if ($request->isMethod('GET')) {
            // ✅ GET → afficher le formulaire d'ajout pour cette publication spécifique
            return $this->render('admin/commentaire/new.html.twig', [
                'publication' => $publication,
            ]);
        }

        // POST : traitement
        $description = trim($request->request->get('description', ''));
        $tags        = trim($request->request->get('tags', ''));

        $errors = [];

        // ✅ Validation Symfony (pas HTML5, pas JS)
        $descViolations = $validator->validate($description, $this->getDescriptionConstraints());
        if (count($descViolations) > 0) {
            $errors['description'] = $descViolations[0]->getMessage();
        }

        if ($tags !== '') {
            $tagsViolations = $validator->validate($tags, $this->getTagsConstraints());
            if (count($tagsViolations) > 0) {
                $errors['tags'] = $tagsViolations[0]->getMessage();
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

        $user = $this->getUser();
        $commentaire->setId($user !== null ? $user->getId() : 1);

        try {
            $em->persist($commentaire);
            $em->flush();
            $this->addFlash('success', '✅ Commentaire ajouté avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur : ' . $e->getMessage());
        }

        // ✅ Rediriger vers les commentaires de CETTE publication (pas le tableau global)
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // API NEW — Forum public (AJAX)
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/api/new/{idP}', name: 'api_commentaire_new', methods: ['POST'])]
    public function apiNew(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $content = $request->getContent();
        $data    = json_decode($content, true);

        if (!is_array($data)) {
            return new JsonResponse(['success' => false, 'field' => 'description', 'error' => 'Les données envoyées sont invalides.'], 400);
        }

        $description = isset($data['description']) ? trim((string) $data['description']) : '';
        $tags        = isset($data['tags'])        ? trim((string) $data['tags'])        : '';

        $descViolations = $validator->validate($description, $this->getDescriptionConstraints());
        if (count($descViolations) > 0) {
            return new JsonResponse(['success' => false, 'field' => 'description', 'error' => $descViolations[0]->getMessage()], 422);
        }

        if ($tags !== '') {
            $tagsViolations = $validator->validate($tags, $this->getTagsConstraints());
            if (count($tagsViolations) > 0) {
                return new JsonResponse(['success' => false, 'field' => 'tags', 'error' => $tagsViolations[0]->getMessage()], 422);
            }
        }

        $commentaire = new Commentaire();
        $commentaire->setPublication($publication);
        $commentaire->setDescription($description);
        $commentaire->setTags($tags);
        $commentaire->setDateCreation(new \DateTime());

        $user = $this->getUser();
        $commentaire->setId($user !== null ? $user->getId() : 1);

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
        $total = count($publication->getCommentaires());

        return new JsonResponse([
            'success' => true,
            'comment' => [
                'id'          => $commentaire->getIdC(),
                'description' => $commentaire->getDescription(),
                'tags'        => $commentaire->getTags() ?? '',
                'date'        => $commentaire->getDateCreation()->format('d/m/Y H:i'),
            ],
            'total' => $total,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // API DELETE
    // ══════════════════════════════════════════════════════════════════════
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
        $em->refresh($publication);
        return new JsonResponse(['success' => true, 'total' => count($publication->getCommentaires())]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // API EDIT
    // ══════════════════════════════════════════════════════════════════════
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

        $descViolations = $validator->validate($description, [
            new Assert\NotBlank(['message' => 'Le commentaire ne peut pas être vide.']),
            new Assert\Length(['min' => 1, 'max' => self::COMMENT_MAX_LENGTH, 'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères.']),
        ]);

        if (count($descViolations) > 0) {
            return new JsonResponse(['success' => false, 'field' => 'description', 'error' => $descViolations[0]->getMessage()], 422);
        }

        if ($tags !== null && $tags !== '') {
            $tagsViolations = $validator->validate($tags, $this->getTagsConstraints());
            if (count($tagsViolations) > 0) {
                return new JsonResponse(['success' => false, 'field' => 'tags', 'error' => $tagsViolations[0]->getMessage()], 422);
            }
        }

        $commentaire->setDescription($description);
        if ($tags !== null) { $commentaire->setTags($tags); }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'field' => null, 'error' => 'Erreur lors de la modification : ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['success' => true, 'description' => $commentaire->getDescription(), 'tags' => $commentaire->getTags() ?? '']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // API GET — Commentaires d'une publication
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/publication/{idP}/comments', name: 'api_publication_comments', methods: ['GET'])]
    public function getPublicationComments(Publication $publication, CommentaireRepository $commentaireRepo): JsonResponse
    {
        $allComments = $commentaireRepo->findBy(['publication' => $publication], ['dateCreation' => 'DESC']);

        $comments = [];
        foreach ($allComments as $comment) {
            if ($comment->getIsCancelled()) continue;
            $comments[] = [
                'id'          => $comment->getIdC(),
                'description' => $comment->getDescription(),
                'tags'        => $comment->getTags() ?? '',
                'date'        => $comment->getDateCreation() ? $comment->getDateCreation()->format('d/m/Y') : '',
            ];
        }

        return new JsonResponse(['success' => true, 'comments' => $comments, 'total' => count($comments)]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // ANNULER
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/{idC}/cancel', name: 'app_commentaire_cancel', methods: ['POST'])]
    public function cancel(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('cancel' . $commentaire->getIdC(), $request->request->get('_token'))) {
            $commentaire->setIsCancelled(true);
            $commentaire->setCancelledAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', '✅ Commentaire annulé avec succès !');
        } else {
            $this->addFlash('error', '❌ Token CSRF invalide.');
        }
        // ✅ Retourner vers les commentaires de la publication concernée
        $publication = $commentaire->getPublication();
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // DÉSANNULER
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/{idC}/uncancel', name: 'app_commentaire_uncancel', methods: ['POST'])]
    public function uncancel(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('uncancel' . $commentaire->getIdC(), $request->request->get('_token'))) {
            $commentaire->setIsCancelled(false);
            $commentaire->setCancelledAt(null);
            $em->flush();
            $this->addFlash('success', '✅ Commentaire désannulé avec succès !');
        } else {
            $this->addFlash('error', '❌ Token CSRF invalide.');
        }
        $publication = $commentaire->getPublication();
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // SIGNALER — avec validation Symfony (motif obligatoire, description min 3)
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/{idC}/report', name: 'app_commentaire_report', methods: ['POST'])]
    public function report(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $publication = $commentaire->getPublication();

        if (!$this->isCsrfTokenValid('report', $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token CSRF invalide.');
            return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
        }

        $reason      = trim($request->request->get('reason', ''));
        $description = trim($request->request->get('description', ''));

        $errors = [];

        // ✅ Validation Symfony — motif obligatoire
        $reasonViolations = $validator->validate($reason, [
            new Assert\NotBlank(['message' => 'Veuillez sélectionner un motif de signalement.']),
            new Assert\Choice([
                'choices'  => ['Langage inapproprié', 'Propos injurieux', 'Discrimination', 'Spam ou publicité', 'Contenu offensant', 'Autre'],
                'message'  => 'Veuillez choisir un motif valide.',
            ]),
        ]);
        if (count($reasonViolations) > 0) {
            $errors['reason'] = $reasonViolations[0]->getMessage();
        }

        // ✅ Validation Symfony — description : si fournie, min 3 caractères
        if ($description !== '') {
            $descViolations = $validator->validate($description, [
                new Assert\Length([
                    'min'        => 3,
                    'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                ]),
            ]);
            if (count($descViolations) > 0) {
                $errors['description'] = $descViolations[0]->getMessage();
            }
        }

        if (!empty($errors)) {
            // ✅ Repasser les erreurs à la vue (via flash ou session)
            foreach ($errors as $err) {
                $this->addFlash('report_error', $err);
            }
            $this->addFlash('report_comment_id', (string) $commentaire->getIdC());
            return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
        }

        $fullReason = $reason . (!empty($description) ? ' - ' . $description : '');
        $commentaire->setIsReported(true);
        $commentaire->setReportReason($fullReason);
        $em->flush();
        $this->addFlash('success', '🚩 Commentaire signalé avec succès !');

        // ✅ Retourner vers les commentaires de la publication
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // DÉSIGNALER
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/{idC}/unreport', name: 'app_commentaire_unreport', methods: ['POST'])]
    public function unreport(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('unreport' . $commentaire->getIdC(), $request->request->get('_token'))) {
            $commentaire->setIsReported(false);
            $commentaire->setReportReason(null);
            $em->flush();
            $this->addFlash('success', '✅ Commentaire désignalé avec succès !');
        } else {
            $this->addFlash('error', '❌ Token CSRF invalide.');
        }
        $publication = $commentaire->getPublication();
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // SHOW
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/{idC}/show', name: 'app_commentaire_show', methods: ['GET'])]
    public function show(Commentaire $commentaire): Response
    {
        return $this->render('admin/commentaire/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // DELETE (Back-office) — redirige vers les commentaires de la publication
    // ══════════════════════════════════════════════════════════════════════
    #[Route('/{idC}', name: 'app_commentaire_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $publication = $commentaire->getPublication();

        if ($this->isCsrfTokenValid('delete' . $commentaire->getIdC(), $request->request->get('_token'))) {
            try {
                $em->remove($commentaire);
                $em->flush();
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
                }
                $this->addFlash('error', '❌ Erreur : ' . $e->getMessage());
                return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
            }

            if ($request->isXmlHttpRequest()) {
                $em->refresh($publication);
                return new JsonResponse(['success' => true, 'total' => count($publication->getCommentaires())]);
            }
            $this->addFlash('success', 'Commentaire supprimé !');
        }

        // ✅ Retourner vers les commentaires de la publication (pas le tableau global)
        return $this->redirectToRoute('app_publication_show', ['idP' => $publication->getIdP()]);
    }
}