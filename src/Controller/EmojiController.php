<?php
// src/Controller/EmojiController.php

namespace App\Controller;

use App\Service\EmojiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur proxy vers l'API EmojiHub (https://emojihub.yurace.pro/api)
 * Toutes les routes sont en GET sauf indication contraire.
 */
#[Route('/api/emojis')]
class EmojiController extends AbstractController
{
    public function __construct(
        private readonly EmojiService $emojiService
    ) {}

    // ─────────────────────────────────────────────────────────────
    // GET /api/emojis/travel?limit=30
    // Emojis liés au voyage (catégorie travel-and-places)
    // ─────────────────────────────────────────────────────────────
    #[Route('/travel', name: 'api_emojis_travel', methods: ['GET'])]
    public function getTravelEmojis(Request $request): JsonResponse
    {
        $limit  = max(1, min(100, $request->query->getInt('limit', 30)));
        $emojis = $this->emojiService->getTravelEmojis($limit);

        return $this->json([
            'success' => true,
            'emojis'  => $emojis,
            'count'   => count($emojis),
            'source'  => 'emojihub',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/emojis/search?q=smile&limit=20
    // Recherche d'emojis par nom
    // ─────────────────────────────────────────────────────────────
    #[Route('/search', name: 'api_emojis_search', methods: ['GET'])]
    public function searchEmojis(Request $request): JsonResponse
    {
        $raw   = $request->query->get('q', '');
        $query = trim(is_string($raw) ? $raw : '');
        $limit = max(1, min(50, $request->query->getInt('limit', 20)));

        if ($query === '') {
            return $this->json([
                'success' => false,
                'error'   => 'Le paramètre "q" est obligatoire.',
            ], 400);
        }

        $emojis = $this->emojiService->searchEmojis($query, $limit);

        return $this->json([
            'success' => true,
            'emojis'  => $emojis,
            'query'   => $query,
            'count'   => count($emojis),
            'source'  => 'emojihub',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/emojis/trending?limit=30
    // Emojis populaires (smileys + travel)
    // ─────────────────────────────────────────────────────────────
    #[Route('/trending', name: 'api_emojis_trending', methods: ['GET'])]
    public function getTrendingEmojis(Request $request): JsonResponse
    {
        $limit  = max(1, min(100, $request->query->getInt('limit', 30)));
        $emojis = $this->emojiService->getTrendingEmojis($limit);

        return $this->json([
            'success' => true,
            'emojis'  => $emojis,
            'count'   => count($emojis),
            'source'  => 'emojihub',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/emojis/category/{category}?limit=30
    // Emojis par catégorie EmojiHub
    // Catégories valides : smileys-and-people, animals-and-nature,
    //   food-and-drink, travel-and-places, activities, objects, symbols, flags
    // ─────────────────────────────────────────────────────────────
    #[Route('/category/{category}', name: 'api_emojis_category', methods: ['GET'])]
    public function getEmojisByCategory(string $category, Request $request): JsonResponse
    {
        $limit  = max(1, min(100, $request->query->getInt('limit', 30)));
        $emojis = $this->emojiService->getEmojisByCategory($category, $limit);

        return $this->json([
            'success'  => true,
            'emojis'   => $emojis,
            'category' => $category,
            'count'    => count($emojis),
            'source'   => 'emojihub',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/emojis/categories
    // Liste toutes les catégories disponibles sur EmojiHub
    // ─────────────────────────────────────────────────────────────
    #[Route('/categories', name: 'api_emojis_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        $categories = $this->emojiService->getCategories();

        return $this->json([
            'success'    => true,
            'categories' => $categories,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/emojis/random?limit=10
    // Emojis aléatoires depuis EmojiHub
    // ─────────────────────────────────────────────────────────────
    #[Route('/random', name: 'api_emojis_random', methods: ['GET'])]
    public function getRandomEmojis(Request $request): JsonResponse
    {
        $limit  = max(1, min(30, $request->query->getInt('limit', 10)));
        $emojis = $this->emojiService->getTravelEmojis($limit);

        return $this->json([
            'success' => true,
            'emojis'  => $emojis,
            'count'   => count($emojis),
            'source'  => 'emojihub',
        ]);
    }
}