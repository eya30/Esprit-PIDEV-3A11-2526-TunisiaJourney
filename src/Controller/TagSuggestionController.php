<?php

namespace App\Controller;

use App\Repository\PublicationRepository;
use App\Service\TagSuggestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tags')]
class TagSuggestionController extends AbstractController
{
    public function __construct(
        private readonly TagSuggestionService $tagSuggestionService
    ) {}

    #[Route('/suggest', name: 'api_tags_suggest_text', methods: ['POST'])]
    public function suggestFromBody(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $text         = isset($data['text'])         ? trim((string) $data['text'])           : '';
        $existingTags = isset($data['existingTags']) ? trim((string) $data['existingTags'])    : null;
        $maxTags      = isset($data['maxTags'])      ? max(3, min(12, (int) $data['maxTags'])) : 8;

        if (strlen($text) < 3) {
            return new JsonResponse([
                'success' => false,
                'tags'    => [],
                'error'   => 'Texte trop court pour générer des tags.',
            ], 422);
        }

        $result = $this->tagSuggestionService->suggestTags($text, $existingTags, $maxTags);

        // Fix lignes 44-45 : les clés existent toujours selon PHPStan, suppression des ??
        $success = (bool) $result['success'];
        $tags    = (array) $result['tags'];
        $error   = $result['error'];

        if (!$success && empty($tags)) {
            return new JsonResponse([
                'success' => false,
                'tags'    => [],
                'error'   => $error ?? 'Erreur de génération de tags.',
            ], 500);
        }

        return new JsonResponse([
            'success' => true,
            'tags'    => $tags,
            'source'  => 'text',
        ]);
    }

    #[Route('/suggest/{idP}', name: 'api_tags_suggest', methods: ['POST'])]
    public function suggest(
        int                   $idP,
        Request               $request,
        PublicationRepository $publicationRepository
    ): JsonResponse {
        $publication = $publicationRepository->find($idP);

        if (!$publication) {
            return new JsonResponse([
                'success' => false,
                'tags'    => [],
                'error'   => 'Publication introuvable.',
            ], 404);
        }

        $data         = json_decode($request->getContent(), true) ?? [];
        $existingTags = isset($data['existingTags']) ? trim((string) $data['existingTags'])    : null;
        $maxTags      = isset($data['maxTags'])      ? max(3, min(12, (int) $data['maxTags'])) : 8;

        $text = trim(($publication->getNom() ?? '') . ' ' . ($publication->getDescription() ?? ''));

        $result = $this->tagSuggestionService->suggestTags($text, $existingTags, $maxTags);

        // Fix lignes 91-92 : idem
        $success = (bool) $result['success'];
        $tags    = (array) $result['tags'];
        $error   = $result['error'];

        if (!$success && empty($tags)) {
            return new JsonResponse([
                'success' => false,
                'tags'    => [],
                'error'   => $error ?? 'Erreur de génération de tags.',
            ], 500);
        }

        return new JsonResponse([
            'success' => true,
            'tags'    => $tags,
            'source'  => 'publication',
        ]);
    }

    #[Route('/suggest-from-text', name: 'api_tags_suggest_from_text', methods: ['POST'])]
    public function suggestFromText(Request $request): JsonResponse
    {
        return $this->suggestFromBody($request);
    }
}