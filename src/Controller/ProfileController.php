<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    // ----------------------------------------------------------------
    // ROUTE DEBUG : tester l'API gratuite
    // ----------------------------------------------------------------
    #[Route('/profile/debug-free', name: 'profile_debug_free', methods: ['GET'])]
    public function debugFree(): JsonResponse
    {
        $hfKey = $_ENV['HF_API_KEY'] ?? getenv('HF_API_KEY') ?? '';

        if (empty($hfKey)) {
            return new JsonResponse([
                'error' => 'HF_API_KEY non configuré',
                'help' => 'Ajoutez votre token Hugging Face dans .env'
            ], 500);
        }

        // Tester avec un modèle gratuit qui fonctionne
        $result = $this->callFreeModel($hfKey, 'anime cat, cute, ghibli style');

        return new JsonResponse($result);
    }

    // ----------------------------------------------------------------
    // ROUTE : tester la configuration
    // ----------------------------------------------------------------
    #[Route('/profile/test-api', name: 'profile_test_api', methods: ['GET'])]
    public function testApi(): JsonResponse
    {
        $hfKey = $_ENV['HF_API_KEY'] ?? getenv('HF_API_KEY') ?? '';

        return new JsonResponse([
            'php_version' => PHP_VERSION,
            'curl_available' => function_exists('curl_version') ? curl_version()['version'] : 'NON',
            'openssl_enabled' => extension_loaded('openssl'),
            'hf_key_set' => !empty($hfKey)
                ? '✅ Oui (' . substr($hfKey, 0, 8) . '...)'
                : '❌ NON — ajoutez HF_API_KEY dans .env',
            'internet' => $this->checkInternet() ? '✅ Connexion internet OK' : '❌ Pas de connexion internet',
            'next_step' => 'Allez sur /profile/debug-free pour tester',
            'info' => 'Les modèles gratuits peuvent prendre 30-60s la première fois (chargement)'
        ]);
    }

    // ----------------------------------------------------------------
    // ROUTE PRINCIPALE - POST uniquement
    // ----------------------------------------------------------------
    #[Route('/profile/proxy-cartoon', name: 'profile_proxy_cartoon', methods: ['POST'])]
    public function proxyCartoon(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file) {
            return new JsonResponse(['error' => 'Aucune image envoyée.'], 400);
        }

        $style = $request->request->get('style', 'anime');

        $hfKey = $_ENV['HF_API_KEY'] ?? getenv('HF_API_KEY') ?? '';
        if (empty($hfKey)) {
            return new JsonResponse([
                'error' => 'HF_API_KEY non configurée dans .env',
                'help' => 'Créez un token gratuit sur huggingface.co/settings/tokens'
            ], 500);
        }

        $imageData = file_get_contents($file->getPathname());
        if ($imageData === false) {
            return new JsonResponse(['error' => "Impossible de lire l'image."], 500);
        }

        // Sauvegarder l'image originale pour le fallback
        $base64Original = base64_encode($imageData);

        // Créer un prompt basé sur le style
        $prompts = [
            'anime' => 'beautiful anime portrait, studio ghibli style, soft colors, detailed face, high quality illustration, cute, artistic',
            'cartoon' => 'cartoon portrait, pixar style, colorful 3d render, friendly face, smooth, high quality, cute',
            'sketch' => 'pencil sketch portrait, detailed line art, black and white drawing, artistic, high quality',
            'oil' => 'oil painting portrait, classical style, rich colors, detailed brushstrokes, masterpiece',
            'pixel' => 'pixel art portrait, 16-bit retro style, game character sprite, colorful, 8-bit aesthetic',
        ];
        $prompt = $prompts[$style] ?? $prompts['anime'];

        // Essayer de générer une image
        $result = $this->callFreeModel($hfKey, $prompt);

        if ($result['success']) {
            return new JsonResponse([
                'success' => true,
                'image' => 'data:image/png;base64,' . base64_encode($result['data']),
                'style' => $style,
                'model' => $result['model'] ?? 'unknown',
                'note' => 'Image générée par IA (style: ' . $style . ')'
            ]);
        }

        // Si échec, retourner l'image originale avec un message
        return new JsonResponse([
            'success' => false,
            'error' => $result['error'] ?? 'Échec de la génération',
            'fallback' => 'data:image/png;base64,' . $base64Original,
            'style' => $style,
            'message' => 'Image originale conservée',
            'debug_url' => '/profile/debug-free'
        ], 200); // 200 pour que le front-end puisse utiliser le fallback
    }

    // ----------------------------------------------------------------
    // Appel aux modèles gratuits qui fonctionnent
    // ----------------------------------------------------------------
    private function callFreeModel(string $hfKey, string $prompt): array
    {
        // Liste des modèles gratuits qui fonctionnent pour text-to-image
        $models = [
            'stabilityai/stable-diffusion-2-1' => [
                'url' => 'https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-2-1',
                'params' => [
                    'negative_prompt' => 'ugly, blurry, bad quality, distorted',
                    'num_inference_steps' => 30,
                    'guidance_scale' => 7.5,
                    'width' => 512,
                    'height' => 512,
                ]
            ],
            'runwayml/stable-diffusion-v1-5' => [
                'url' => 'https://api-inference.huggingface.co/models/runwayml/stable-diffusion-v1-5',
                'params' => [
                    'negative_prompt' => 'ugly, blurry, bad quality, distorted',
                    'num_inference_steps' => 30,
                    'guidance_scale' => 7.5,
                    'width' => 512,
                    'height' => 512,
                ]
            ],
            'prompthero/openjourney' => [
                'url' => 'https://api-inference.huggingface.co/models/prompthero/openjourney',
                'params' => [
                    'negative_prompt' => 'ugly, bad quality',
                    'num_inference_steps' => 30,
                    'guidance_scale' => 7,
                    'width' => 512,
                    'height' => 512,
                ]
            ],
            'dreamlike-art/dreamlike-photoreal-2.0' => [
                'url' => 'https://api-inference.huggingface.co/models/dreamlike-art/dreamlike-photoreal-2.0',
                'params' => [
                    'negative_prompt' => 'ugly, blurry, low quality',
                    'num_inference_steps' => 30,
                    'guidance_scale' => 7,
                    'width' => 512,
                    'height' => 512,
                ]
            ],
        ];

        foreach ($models as $modelName => $config) {
            $payload = json_encode([
                'inputs' => $prompt,
                'parameters' => $config['params'],
            ]);

            $ch = curl_init($config['url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $hfKey,
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                continue; // Essayer le modèle suivant
            }

            // Si le modèle est en chargement (503), on attend et on réessaie une fois
            if ($httpCode === 503) {
                $json = @json_decode($response, true);
                if (isset($json['estimated_time'])) {
                    sleep(min($json['estimated_time'], 30));
                } else {
                    sleep(20);
                }
                
                // Réessayer
                $ch = curl_init($config['url']);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_TIMEOUT => 120,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $hfKey,
                        'Content-Type: application/json',
                    ],
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }

            // Vérifier si c'est une image
            if ($httpCode === 200 && strlen($response) > 1000) {
                $isJpeg = substr($response, 0, 3) === "\xFF\xD8\xFF";
                $isPng = substr($response, 0, 8) === "\x89PNG\r\n\x1a\n";
                $isWebp = substr($response, 0, 4) === "RIFF" && substr($response, 8, 4) === "WEBP";
                
                if ($isJpeg || $isPng || $isWebp) {
                    return [
                        'success' => true,
                        'data' => $response,
                        'model' => $modelName,
                    ];
                }
            }

            // Erreur d'authentification = arrêter tout
            if (in_array($httpCode, [401, 403])) {
                return [
                    'success' => false,
                    'error' => 'Token Hugging Face invalide (HTTP ' . $httpCode . ')',
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Tous les modèles sont indisponibles. Réessayez dans quelques minutes.',
        ];
    }

    // ----------------------------------------------------------------
    // Vérifier la connexion internet
    // ----------------------------------------------------------------
    private function checkInternet(): bool
    {
        $ch = curl_init('https://api-inference.huggingface.co');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode > 0;
    }
}