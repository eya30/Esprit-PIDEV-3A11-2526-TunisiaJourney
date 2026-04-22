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
        ]);
    }

    #[Route('/hotel/{id}', name: 'app_translate_hotel_page', methods: ['GET'])]
    public function translateHotel(int $id, Request $request, Connection $connection, MyMemoryTranslateService $translator): Response
    {
        $lang = $request->query->get('lang', 'en');
        
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
        ]);
    }
}