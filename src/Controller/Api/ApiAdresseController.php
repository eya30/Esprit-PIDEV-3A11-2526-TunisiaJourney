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
<<<<<<< HEAD
        $query = $request->query->getString('query', '');

        if (strlen(trim($query)) < 2) {
            return new JsonResponse([]);
        }

        try {
            $response = $client->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q'               => $query,
                    'format'          => 'json',
                    'limit'           => 8,
                    'countrycodes'    => 'tn',
                    'accept-language' => 'fr',
                    'addressdetails'  => 0,
=======
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
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                ],
                'headers' => [
                    'User-Agent' => 'TunisiaJourney/1.0',
                ],
                'timeout' => 10,
            ]);
<<<<<<< HEAD

            $data    = $response->toArray();
            $results = [];

            foreach ($data as $item) {
                $displayName = $item['display_name'] ?? '';
=======
            
            $data = $response->toArray();
            $results = [];
            
            foreach ($data as $item) {
                $displayName = $item['display_name'] ?? '';
                // Nettoyer l'affichage
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                $displayName = str_replace(', Tunisie', '', $displayName);
                $displayName = str_replace(', Tunisia', '', $displayName);
                if (!empty($displayName)) {
                    $results[] = $displayName;
                }
            }
<<<<<<< HEAD

            return new JsonResponse($results);

        } catch (\Exception $e) {
            return new JsonResponse($this->getFallbackAddresses($query));
        }
    }

    /**
     * @return list<string>
     */
=======
            
            return new JsonResponse($results);
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des adresses par défaut
            return new JsonResponse($this->getFallbackAddresses($query));
        }
    }
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD

        $query = strtolower($query);

        $filtered = array_filter($addresses, function (string $addr) use ($query): bool {
            return str_contains(strtolower($addr), $query);
        });

        /** @var list<string> $result */
        $result = array_slice(array_values($filtered), 0, 8);

        return $result;
=======
        
        $query = strtolower($query);
        $results = array_filter($addresses, function($addr) use ($query) {
            return strpos(strtolower($addr), $query) !== false;
        });
        
        return array_values(array_slice($results, 0, 8));
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }
}