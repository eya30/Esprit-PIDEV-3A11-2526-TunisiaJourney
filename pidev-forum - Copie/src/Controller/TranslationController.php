<?php
// src/Controller/TranslationController.php

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/translation')]
class TranslationController extends AbstractController
{
    private TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    #[Route('/translate', name: 'api_translate_text', methods: ['POST'])]
    public function translateText(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $targetLang = $data['targetLang'] ?? 'fr';
        $sourceLang = $data['sourceLang'] ?? null;

        if (empty($text)) {
            return $this->json(['success' => false, 'error' => 'Texte vide'], 400);
        }

        if (!$this->translationService->isLanguageSupported($targetLang)) {
            return $this->json(['success' => false, 'error' => 'Langue non supportée'], 422);
        }

        try {
            $translated = $this->translationService->translate($text, $targetLang, $sourceLang);
            $detectedLang = $this->translationService->detectLanguage($text);

            return $this->json([
                'success' => true,
                'original' => $text,
                'translated' => $translated,
                'targetLang' => $targetLang,
                'detectedLang' => $detectedLang,
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/translate-batch', name: 'api_translate_batch', methods: ['POST'])]
    public function translateBatch(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $texts = $data['texts'] ?? [];
        $targetLang = $data['targetLang'] ?? 'fr';

        if (empty($texts)) {
            return $this->json(['success' => false, 'error' => 'Aucun texte à traduire'], 400);
        }

        if (!$this->translationService->isLanguageSupported($targetLang)) {
            return $this->json(['success' => false, 'error' => 'Langue non supportée'], 422);
        }

        try {
            $translations = $this->translationService->translateBatch($texts, $targetLang);
            return $this->json(['success' => true, 'translations' => $translations]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/translate-page', name: 'api_translate_page', methods: ['POST'])]
    public function translatePage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['texts']) && is_array($data['texts'])) {
            $texts = $data['texts'];
            $targetLang = $data['targetLang'] ?? 'fr';
        } elseif (isset($data['text']) && is_string($data['text'])) {
            $texts = [$data['text']];
            $targetLang = $data['targetLang'] ?? 'fr';
        } else {
            return $this->json(['success' => false, 'error' => 'Format invalide'], 400);
        }

        if (empty($texts)) {
            return $this->json(['success' => false, 'error' => 'Aucun texte à traduire'], 400);
        }

        if (!$this->translationService->isLanguageSupported($targetLang)) {
            return $this->json(['success' => false, 'error' => 'Langue non supportée'], 422);
        }

        try {
            $translations = $this->translationService->translateBatch($texts, $targetLang);
            
            if (isset($data['text']) && is_string($data['text'])) {
                return $this->json([
                    'success' => true,
                    'translated' => $translations[0],
                    'original' => $texts[0],
                    'targetLang' => $targetLang,
                ]);
            }
            
            return $this->json([
                'success' => true,
                'translations' => $translations,
                'targetLang' => $targetLang,
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/languages', name: 'api_translation_languages', methods: ['GET'])]
    public function getLanguages(): JsonResponse
    {
        $languages = [];
        foreach ($this->translationService->getSupportedLanguages() as $code => $name) {
            $languages[] = [
                'code' => $code,
                'name' => $name,
                'flag' => $this->translationService->getFlagEmoji($code),
            ];
        }
        return $this->json(['success' => true, 'languages' => $languages]);
    }

    #[Route('/detect', name: 'api_detect_language', methods: ['POST'])]
    public function detectLanguage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';

        if (empty($text)) {
            return $this->json(['success' => false, 'error' => 'Texte vide'], 400);
        }

        $detected = $this->translationService->detectLanguage($text);
        return $this->json(['success' => true, 'detectedLang' => $detected]);
    }
}