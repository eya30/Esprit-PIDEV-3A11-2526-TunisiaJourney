<?php
// src/Controller/TagSuggestionController.php

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

    /**
     * POST /api/tags/suggest
     * Appelé par le frontend (forum_public + watch) avec { text: "..." }
     */
    #[Route('/suggest', name: 'api_tags_suggest_text', methods: ['POST'])]
    public function suggestFromBody(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $text         = isset($data['text'])         ? trim((string) $data['text'])              : '';
        $existingTags = isset($data['existingTags']) ? trim((string) $data['existingTags'])       : null;
        $maxTags      = isset($data['maxTags'])      ? max(3, min(12, (int) $data['maxTags']))    : 8;

        if (strlen($text) < 3) {
            return new JsonResponse([
                'success' => false,
                'tags'    => [],
                'error'   => 'Texte trop court pour générer des tags.',
            ], 422);
        }

        $result = $this->tagSuggestionService->suggestTags($text, $existingTags, $maxTags);

        // Normalisation défensive : on s'assure que les clés existent toujours
        $success = (bool) ($result['success'] ?? false);
        $tags    = (array) ($result['tags']    ?? []);
        $error   = $result['error'] ?? null;

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

    /**
     * POST /api/tags/suggest/{idP}
     * Génère les tags depuis le titre + description de la publication
     */
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

        $success = (bool) ($result['success'] ?? false);
        $tags    = (array) ($result['tags']    ?? []);
        $error   = $result['error'] ?? null;

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

    /**
     * POST /api/tags/suggest-from-text
     * Alias explicite (utilisé par tag-suggestion.js côté JS si besoin)
     */
    #[Route('/suggest-from-text', name: 'api_tags_suggest_from_text', methods: ['POST'])]
    public function suggestFromText(Request $request): JsonResponse
    {
        return $this->suggestFromBody($request);
    }
}