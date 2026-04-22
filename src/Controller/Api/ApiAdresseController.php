<?php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiAdresseController extends AbstractController
{
    #[Route('/api/adresse/autocomplete', name: 'api_adresse_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request, HttpClientInterface $client): JsonResponse
    {
        $query = $request->query->get('query', '');
        
        if (strlen(trim($query)) < 2) {
            return new JsonResponse([]);
        }
        
        try {
            // Appel à Nominatim OpenStreetMap
            $response = $client->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 8,
                    'countrycodes' => 'tn',
                    'accept-language' => 'fr',
                    'addressdetails' => 0,
                ],
                'headers' => [
                    'User-Agent' => 'TunisiaJourney/1.0',
                ],
                'timeout' => 10,
            ]);
            
            $data = $response->toArray();
            $results = [];
            
            foreach ($data as $item) {
                $displayName = $item['display_name'] ?? '';
                // Nettoyer l'affichage
                $displayName = str_replace(', Tunisie', '', $displayName);
                $displayName = str_replace(', Tunisia', '', $displayName);
                if (!empty($displayName)) {
                    $results[] = $displayName;
                }
            }
            
            return new JsonResponse($results);
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des adresses par défaut
            return new JsonResponse($this->getFallbackAddresses($query));
        }
    }
    
    private function getFallbackAddresses(string $query): array
    {
        $addresses = [
            "Avenue Habib Bourguiba, Tunis",
            "Rue de la Liberté, Tunis",
            "Avenue Mohamed V, Tunis",
            "Rue de Marseille, Tunis",
            "Boulevard du 7 Novembre, Tunis",
            "Place du 14 Janvier, Tunis",
            "Avenue Farhat Hached, Sousse",
            "Rue de Sousse, Sousse",
            "Boulevard Hedi Chaker, Sfax",
            "Rue Habib Thameur, La Marsa",
            "Avenue de Carthage, Carthage",
            "Rue de la Plage, Hammamet",
        ];
        
        $query = strtolower($query);
        $results = array_filter($addresses, function($addr) use ($query) {
            return strpos(strtolower($addr), $query) !== false;
        });
        
        return array_values(array_slice($results, 0, 8));
    }
}