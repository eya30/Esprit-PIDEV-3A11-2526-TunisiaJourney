<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProfileForumController extends AbstractController
{
    #[Route('/profile/debug-free', name: 'profile_debug_free', methods: ['GET'])]
    public function debugFree(): JsonResponse
    {
        $replicateToken = $_ENV['REPLICATE_API_TOKEN'] ?? '';
       
        $models = [
            'ac732df83cea7fff18b8472768c88ad041fa750ff7682a21affe81863cbe77e4' => 'Image to Image (style transfer)',
            '42fed1c4974146d4d2414e2be2c5277c7fcf05fcc3a73abf41610695738c1d7b' => 'Image to Image (simplified)',
            '8beff3369e81422112d93e89bc5a08f284c5fbb5f9cdf28b3451d2e2a02e4a7a' => 'Stable Diffusion (text to image)',
        ];
       
        return new JsonResponse([
            'replicate_configured' => !empty($replicateToken) ? '✅ Oui' : '❌ NON',
            'token_preview' => !empty($replicateToken) ? substr($replicateToken, 0, 8) . '...' : 'NON CONFIGURÉ',
            'available_models' => $models,
            'instruction' => 'Ajoutez REPLICATE_API_TOKEN=votre_token dans .env',
            'get_token_url' => 'https://replicate.com/account/api-tokens'
        ]);
    }

    #[Route('/profile/test-api', name: 'profile_test_api', methods: ['GET'])]
    public function testApi(): JsonResponse
    {
        $replicateToken = $_ENV['REPLICATE_API_TOKEN'] ?? '';

        // Fix PHPStan :39 — curl_version() returns array|false; guard before offset access
        $curlInfo    = curl_version();
        $curlVersion = is_array($curlInfo) ? $curlInfo['version'] : 'NON';

        return new JsonResponse([
            'php_version'       => PHP_VERSION,
            'curl_available'    => $curlVersion,
            'openssl_enabled'   => extension_loaded('openssl'),
            'replicate_key_set' => !empty($replicateToken)
                ? '✅ Oui (' . substr($replicateToken, 0, 8) . '...)'
                : '❌ NON — ajoutez REPLICATE_API_TOKEN dans .env',
            'internet' => $this->checkInternet() ? '✅ OK' : '❌ Pas de connexion',
            'replicate_url' => 'https://api.replicate.com',
            'help' => 'Créez un token sur replicate.com/account/api-tokens'
        ]);
    }

    #[Route('/profile/proxy-cartoon', name: 'profile_proxy_cartoon', methods: ['POST'])]
    public function proxyCartoon(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file) {
            return new JsonResponse(['error' => 'Aucune image envoyée.'], 400);
        }

        $style          = $request->request->get('style', 'anime');
        $replicateToken = $_ENV['REPLICATE_API_TOKEN'] ?? '';

        if (empty($replicateToken)) {
            return new JsonResponse([
                'error' => 'REPLICATE_API_TOKEN non configuré dans .env',
                'help' => 'Ajoutez REPLICATE_API_TOKEN=votre_token dans .env',
                'get_token_url' => 'https://replicate.com/account/api-tokens'
            ], 500);
        }

        $imageData = file_get_contents($file->getPathname());
        if ($imageData === false) {
            return new JsonResponse(['error' => 'Impossible de lire le fichier image.'], 400);
        }
        $base64Image = 'data:' . $file->getMimeType() . ';base64,' . base64_encode($imageData);

        $prompts = [
            'anime'   => 'anime style portrait, studio ghibli, soft colors, beautiful, high quality, detailed face',
            'cartoon' => 'cartoon pixar style portrait, colorful, friendly, 3d render, high quality',
            'sketch'  => 'pencil sketch portrait, black and white, detailed line art, artistic',
            'oil'     => 'oil painting portrait, classical style, rich colors, masterpiece, van gogh style',
            'pixel'   => 'pixel art portrait, retro 16-bit style, colorful, video game character',
        ];

        // Fix PHPStan :89 & :101 — $style is mixed (from request->get()); cast to string
        // then use a known-safe key via array_key_exists check
        $styleKey   = is_string($style) ? $style : 'anime';
        $stylePrompt = array_key_exists($styleKey, $prompts) ? $prompts[$styleKey] : $prompts['anime'];

        $modelsToTry = [
            [
                'name'    => 'image-to-image-style-transfer',
                'version' => 'ac732df83cea7fff18b8472768c88ad041fa750ff7682a21affe81863cbe77e4',
                'input'   => [
                    'image'               => $base64Image,
                    'prompt'              => $stylePrompt,
                    'prompt_strength'     => 0.7,
                    'num_inference_steps' => 25,
                    'guidance_scale'      => 7.5,
                    'negative_prompt'     => 'ugly, blurry, low quality, distorted',
                ],
            ],
            [
                'name'    => 'image-to-image-simplified',
                'version' => '42fed1c4974146d4d2414e2be2c5277c7fcf05fcc3a73abf41610695738c1d7b',
                'input'   => [
                    'image'  => $base64Image,
                    'prompt' => $stylePrompt,
                ],
            ],
        ];

        foreach ($modelsToTry as $modelConfig) {
            $result = $this->callReplicateModel($replicateToken, $modelConfig);

            // Fix PHPStan :112 — $result['image_url'] might not exist; use isset guard
            if ($result['success'] && isset($result['image_url'])) {
                return new JsonResponse([
                    'success' => true,
                    'image'   => $result['image_url'],
                    'style'   => $styleKey,
                    'model'   => $modelConfig['name'],
                    'note'    => 'Image transformée en style ' . $styleKey,
                ]);
            }
        }

        return new JsonResponse([
            'success'  => false,
            'fallback' => 'data:image/png;base64,' . base64_encode($imageData),
            'style'    => $styleKey,
            'message'  => 'Transformation non disponible - image originale conservée',
            'error'    => 'Tous les modèles ont échoué. Réessayez plus tard.'
        ]);
    }

    /**
     * @param array{name: string, version: string, input: array<string, mixed>} $modelConfig
     * @return array{success: bool, image_url?: string, error?: string}
     */
    private function callReplicateModel(string $token, array $modelConfig): array
    {
        // Étape 1 : Créer la prédiction
        $ch = curl_init('https://api.replicate.com/v1/predictions');

        // Fix PHPStan :137 — CURLOPT_POSTFIELDS (10015) expects array|string, but
        // json_encode() returns string|false. Provide a guaranteed string fallback.
        $postFields = json_encode([
            'version' => $modelConfig['version'],
            'input'   => $modelConfig['input'],
        ]);
        if ($postFields === false) {
            return ['success' => false, 'error' => 'Erreur d\'encodage JSON'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Token ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $postFields,  // now always string, never false
            CURLOPT_TIMEOUT    => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201 || !is_string($response)) {
            return ['success' => false, 'error' => "HTTP $httpCode lors de la création"];
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($response, true);

        if (!is_array($data)) {
            return ['success' => false, 'error' => 'Réponse JSON invalide'];
        }

        $predictionId = isset($data['id']) && is_string($data['id']) ? $data['id'] : null;

        if (!$predictionId) {
            return ['success' => false, 'error' => 'Pas d\'ID de prédiction'];
        }

        // Étape 2 : Polling (max 60 secondes)
        $urls   = isset($data['urls']) && is_array($data['urls']) ? $data['urls'] : [];
        $getUrl = isset($urls['get']) && is_string($urls['get'])
            ? $urls['get']
            : 'https://api.replicate.com/v1/predictions/' . $predictionId;

        for ($i = 0; $i < 30; $i++) {
            sleep(2);

            $ch = curl_init($getUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Token ' . $token,
                ],
            ]);

            $pollResponse = curl_exec($ch);
            curl_close($ch);

            if (!is_string($pollResponse)) {
                continue;
            }

            /** @var array<string, mixed>|null $pollData */
            $pollData = json_decode($pollResponse, true);

            if (!is_array($pollData)) {
                continue;
            }

            $status = isset($pollData['status']) && is_string($pollData['status'])
                ? $pollData['status']
                : '';

            if ($status === 'succeeded') {
                $output = $pollData['output'] ?? null;

                $imageUrl = null;
                if (is_array($output)) {
                    $first    = $output[0] ?? null;
                    $imageUrl = is_string($first) ? $first : null;
                } elseif (is_string($output)) {
                    $imageUrl = $output;
                }

                if ($imageUrl !== null) {
                    return ['success' => true, 'image_url' => $imageUrl];
                }

                return ['success' => false, 'error' => 'Pas d\'URL dans la réponse'];
            }

            if ($status === 'failed') {
                $error = isset($pollData['error']) && is_string($pollData['error'])
                    ? $pollData['error']
                    : 'Erreur inconnue';
                return ['success' => false, 'error' => $error];
            }
        }

        return ['success' => false, 'error' => 'Timeout après 60 secondes'];
    }

    private function checkInternet(): bool
    {
        $ch = curl_init('https://api.replicate.com');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_NOBODY         => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode > 0;
    }
}