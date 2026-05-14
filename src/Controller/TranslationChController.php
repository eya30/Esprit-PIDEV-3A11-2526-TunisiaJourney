<?php

namespace App\Controller;

use App\Service\MyMemoryTranslateService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/traduction')]
class TranslationChController extends AbstractController
{
    #[Route('/chambre/{id}', name: 'app_translate_chambre_page', methods: ['GET'])]
    public function translateChambre(int $id, Request $request, Connection $connection, MyMemoryTranslateService $translator): Response
    {
        $lang = $request->query->get('lang', 'en');
<<<<<<< HEAD
       
        // ✅ Correction : s'assurer que $lang est une chaîne de caractères
        if (!is_string($lang)) {
            $lang = 'en';
        }
       
        $sql = "SELECT c.type, c.description, h.nom as hotel_nom, h.ville as hotel_ville
                FROM chambre c
                JOIN hotel h ON c.idH = h.idH
                WHERE c.idCh = ?";
        $chambre = $connection->fetchAssociative($sql, [$id]);
       
        if (!$chambre) {
            return $this->json(['error' => 'Chambre non trouvée'], 404);
        }
       
        // ✅ Correction : s'assurer que les valeurs sont des chaînes
        $type = is_string($chambre['type']) ? $chambre['type'] : '';
        $description = is_string($chambre['description']) ? $chambre['description'] : '';
        $hotelNom = is_string($chambre['hotel_nom']) ? $chambre['hotel_nom'] : '';
        $hotelVille = is_string($chambre['hotel_ville']) ? $chambre['hotel_ville'] : '';
       
        return $this->json([
            'type' => $translator->translate($type, $lang),
            'description' => $translator->translate($description, $lang),
            'hotel_nom' => $translator->translate($hotelNom, $lang),
            'hotel_ville' => $translator->translate($hotelVille, $lang),
=======
        
        $sql = "SELECT c.type, c.description, h.nom as hotel_nom, h.ville as hotel_ville 
                FROM chambre c 
                JOIN hotel h ON c.idH = h.idH 
                WHERE c.idCh = ?";
        $chambre = $connection->fetchAssociative($sql, [$id]);
        
        if (!$chambre) {
            return $this->json(['error' => 'Chambre non trouvée'], 404);
        }
        
        return $this->json([
            'type' => $translator->translate($chambre['type'], $lang),
            'description' => $translator->translate($chambre['description'], $lang),
            'hotel_nom' => $translator->translate($chambre['hotel_nom'], $lang),
            'hotel_ville' => $translator->translate($chambre['hotel_ville'], $lang),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        ]);
    }

    #[Route('/hotel/{id}', name: 'app_translate_hotel_page', methods: ['GET'])]
    public function translateHotel(int $id, Request $request, Connection $connection, MyMemoryTranslateService $translator): Response
    {
        $lang = $request->query->get('lang', 'en');
<<<<<<< HEAD
       
        // ✅ Correction : s'assurer que $lang est une chaîne de caractères
        if (!is_string($lang)) {
            $lang = 'en';
        }
       
        $sql = "SELECT nom, description, ville, adresse FROM hotel WHERE idH = ?";
        $hotel = $connection->fetchAssociative($sql, [$id]);
       
        if (!$hotel) {
            return $this->json(['error' => 'Hôtel non trouvé'], 404);
        }
       
        // ✅ Correction : s'assurer que les valeurs sont des chaînes
        $nom = is_string($hotel['nom']) ? $hotel['nom'] : '';
        $description = is_string($hotel['description']) ? $hotel['description'] : '';
        $ville = is_string($hotel['ville']) ? $hotel['ville'] : '';
        $adresse = is_string($hotel['adresse']) ? $hotel['adresse'] : '';
       
        return $this->json([
            'nom' => $translator->translate($nom, $lang),
            'description' => $translator->translate($description, $lang),
            'ville' => $translator->translate($ville, $lang),
            'adresse' => $translator->translate($adresse, $lang),
=======
        
        $sql = "SELECT nom, description, ville, adresse FROM hotel WHERE idH = ?";
        $hotel = $connection->fetchAssociative($sql, [$id]);
        
        if (!$hotel) {
            return $this->json(['error' => 'Hôtel non trouvé'], 404);
        }
        
        return $this->json([
            'nom' => $translator->translate($hotel['nom'], $lang),
            'description' => $translator->translate($hotel['description'], $lang),
            'ville' => $translator->translate($hotel['ville'], $lang),
            'adresse' => $translator->translate($hotel['adresse'], $lang),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        ]);
    }
}