<?php
// src/Controller/ChatbotController.php

namespace App\Controller;

use App\Service\GeminiChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    public function __construct(
        private GeminiChatbotService $gemini
    ) {}

    #[Route('/api/chat/send', name: 'api_chat_send', methods: ['POST'])]
    public function send(Request $request, SessionInterface $session): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        // ── Validation ────────────────────────────────────────────────────
        if (empty($message)) {
            return $this->json([
                'success'  => false,
                'response' => '⚠️ Votre message est vide.',
                'error'    => 'Message vide',
            ], 400);
        }

        if (mb_strlen($message) > 500) {
            return $this->json([
                'success'  => false,
                'response' => '⚠️ Votre message est trop long (500 caractères maximum).',
                'error'    => 'Message trop long',
            ], 400);
        }

        // ── Historique de session ────────────────────────────────────────
        $sessionKey = 'chat_history_' . ($data['session_id'] ?? 'default');
        $history    = $session->get($sessionKey, []);

        // ── Appel Gemini ─────────────────────────────────────────────────
        $result = $this->gemini->sendMessage($message, $history);

        // ── Toujours renvoyer un champ "response" utilisable par le JS ───
        if (!$result['success']) {
            return $this->json([
                'success'  => false,
                'response' => $result['response'] ?? '❌ Une erreur s\'est produite. Veuillez réessayer.',
                'error'    => $result['error'] ?? 'Erreur inconnue',
            ]);
        }

        // ── Sauvegarder l'historique (max 20 messages = 10 échanges) ────
        $history[] = ['role' => 'user',      'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $result['response']];

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        $session->set($sessionKey, $history);

        return $this->json([
            'success'  => true,
            'response' => $result['response'],
        ]);
    }

    #[Route('/api/chat/reset', name: 'api_chat_reset', methods: ['POST'])]
    public function reset(Request $request, SessionInterface $session): JsonResponse
    {
        $data       = json_decode($request->getContent(), true);
        $sessionKey = 'chat_history_' . ($data['session_id'] ?? 'default');
        $session->remove($sessionKey);

        return $this->json(['success' => true]);
    }
}