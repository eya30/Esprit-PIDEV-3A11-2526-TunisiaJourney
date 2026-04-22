<?php
// src/Controller/AiOllamaController.php

namespace App\Controller;

use App\Service\OllamaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ai')]
class AiOllamaController extends AbstractController
{
    #[Route('/generate-content', name: 'api_ai_generate_content', methods: ['POST'])]
    public function generateContent(Request $request, OllamaService $ollama): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ville = trim($data['ville'] ?? '');

        if (empty($ville)) {
            return new JsonResponse(['error' => 'Veuillez entrer une ville'], 400);
        }

        $ville = strip_tags(substr($ville, 0, 100));
        
        // Appel intelligent qui analyse la ville et cherche les médias
        $suggestions = $ollama->generateContentForVille($ville);

        return new JsonResponse($suggestions);
    }
}
