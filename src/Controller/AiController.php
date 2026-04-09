<?php

namespace App\Controller;

use App\Service\GeminiAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ai')]
class AiController extends AbstractController
{
    #[Route('/ask', name: 'app_ai_ask', methods: ['POST'])]
    public function ask(Request $request, GeminiAIService $aiService): JsonResponse
    {
        $question = $request->request->get('question');
        
        if (empty($question)) {
            return $this->json([
                'success' => false,
                'error' => 'Veuillez poser une question.'
            ], 400);
        }
        
        $response = $aiService->ask($question);
        
        return $this->json($response);
    }
    
    #[Route('/assistant', name: 'app_ai_assistant')]
    public function assistant(): Response
    {
        return $this->render('ai/assistant.html.twig');
    }
}