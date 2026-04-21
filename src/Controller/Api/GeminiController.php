<?php
namespace App\Controller\Api;

use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/gemini')]
class GeminiController extends AbstractController
{
    #[Route('/generer-description', name: 'api_gemini_description', methods: ['POST'])]
    public function generateDescription(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['productName'])) {
            return $this->json(['error' => 'Nom du produit requis'], 400);
        }
        
        $options = [
            'category' => $data['category'] ?? '',
            'material' => $data['material'] ?? '',
            'origin' => $data['origin'] ?? '',
        ];
        
        $description = $gemini->generateProductDescription($data['productName'], $options);
        
        return $this->json([
            'success' => true,
            'description' => $description
        ]);
    }
    
    #[Route('/generer-seo', name: 'api_gemini_seo', methods: ['POST'])]
    public function generateSEO(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['productName'])) {
            return $this->json(['error' => 'Nom du produit requis'], 400);
        }
        
        $seoDescription = $gemini->generateSEODescription(
            $data['productName'],
            $data['category'] ?? ''
        );
        
        return $this->json([
            'success' => true,
            'seoDescription' => $seoDescription
        ]);
    }
    
    #[Route('/generer-detaillee', name: 'api_gemini_detailed', methods: ['POST'])]
    public function generateDetailed(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['productName'])) {
            return $this->json(['error' => 'Nom du produit requis'], 400);
        }
        
        $features = $data['features'] ?? [];
        
        $description = $gemini->generateDetailedDescription($data['productName'], $features);
        
        return $this->json([
            'success' => true,
            'description' => $description
        ]);
    }
    
    #[Route('/suggerer-mots-cles', name: 'api_gemini_keywords', methods: ['POST'])]
    public function suggestKeywords(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['productName'])) {
            return $this->json(['error' => 'Nom du produit requis'], 400);
        }
        
        $keywords = $gemini->suggestKeywords($data['productName']);
        
        return $this->json([
            'success' => true,
            'keywords' => $keywords
        ]);
    }
}