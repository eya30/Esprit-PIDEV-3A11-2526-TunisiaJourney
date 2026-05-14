<?php
// src/Service/MediaIntelligentService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MediaIntelligentService
{
    private HttpClientInterface $client;

    /** @var array<string, array<int, string>> */
    private const VIDEOS_DB = [
        'djerba'        => ['8X9QqP9lX2k', 'dQw4w9WgXcQ', 'tunisia_djerba_2023'],
        'tunis'         => ['0P3tZqyL3gE', 'F3zM_o372Ww', 'tunis_medina_walk'],
        'hammamet'      => ['hammamet_yasmine_guide', 'medina_hammamet_night', 'tunisia_resort'],
        'sousse'        => ['sousse_medina_tour', 'port_el_kantaoui', 'sousse_fortress'],
        'tozeur'        => ['tozeur_oasis_4k', 'chott_el_djerid_salt', 'star_wars_tunisia'],
        'carthage'      => ['9QpV5MYwK1E', 'carthage_ruins_tour', 'roman_africa'],
        'sidi bou said' => ['ZrdbwT8G8eQ', 'sidi_bou_said_blue', 'tunisia_village'],
        'tabarka'       => ['tabarka_nature', 'corail_beach_tunisia'],
        'douz'          => ['douz_sahara_gateway', 'festival_sahara', 'camel_trek'],
        'tataouine'     => ['tataouine_ksar_starwars', 'chenini_village'],
        'default'       => ['tunisia_travel_guide', 'best_of_tunisia', 'tunisia_4k_drone'],
    ];

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Cherche des images intelligentes basées sur les mots-clés IA
     *
     * @param  array<int, string> $motsCles
     * @return array<int, string>
     */
    public function chercherImagesIntelligentes(string $ville, array $motsCles): array
    {
        /** @var array<int, string> $images */
        $images = [];

        // Stratégie 1 : Wikimedia Commons avec recherche contextuelle
        foreach ($motsCles as $mot) {
            $url = $this->searchWikimedia($mot);
            if ($url !== null) {
                $images[] = $url;
            }
        }

        // Stratégie 2 : Fallback thématique si moins de 3 images trouvées
        if (count($images) < 3) {
            $images[] = 'https://loremflickr.com/800/600/' . urlencode("{$ville},tunisia") . '?lock=' . rand(1, 999);
            $images[] = 'https://loremflickr.com/800/600/' . urlencode("{$ville},medina")  . '?lock=' . rand(1, 999);
            $images[] = 'https://loremflickr.com/800/600/tunisia,landscape?lock='           . rand(1, 999);
        }

        return $images;
    }

    private function searchWikimedia(string $searchTerm): ?string
    {
        try {
            $term = urlencode($searchTerm);
            $url  = "https://commons.wikimedia.org/w/api.php?action=query&list=search"
                  . "&srsearch={$term}&srnamespace=6&srlimit=1&format=json&origin=*";

            $response = $this->client->request('GET', $url, ['timeout' => 5]);
            $data     = $response->toArray();

            if (isset($data['query']['search'][0]['title'])) {
                /** @var string $title */
                $title    = $data['query']['search'][0]['title'];
                $filename = str_replace(' ', '_', str_replace('File:', '', $title));

                return "https://commons.wikimedia.org/wiki/Special:FilePath/{$filename}?width=800";
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return null;
    }

    /**
     * Cherche une vidéo intelligente pour la ville
     */
    public function chercherVideoIntelligente(string $ville, string $motCleVideo): ?string
    {
        $villeLower = strtolower($ville);

        $videos = self::VIDEOS_DB[$villeLower] ?? self::VIDEOS_DB['default'];

        $id = $videos[array_rand($videos)];

        return "https://www.youtube.com/embed/{$id}";
    }
}