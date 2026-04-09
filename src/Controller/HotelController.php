<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/hotel')]
class HotelController extends AbstractController
{
    #[Route('/', name: 'app_hotel_index')]
    public function index(Connection $connection): Response
    {
        $hotels = $connection->fetchAllAssociative("SELECT * FROM hotel ORDER BY idH DESC");
        
        return $this->render('hotel/index.html.twig', [
            'hotels' => $hotels,
        ]);
    }
    
    #[Route('/{idH}', name: 'app_hotel_show')]
    public function show(Connection $connection, int $idH): Response
    {
        $hotel = $connection->fetchAssociative("SELECT * FROM hotel WHERE idH = ?", [$idH]);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé');
        }
        
        $chambres = $connection->fetchAllAssociative("SELECT * FROM chambre WHERE idH = ? ORDER BY idCh ASC", [$idH]);
        
        return $this->render('hotel/show.html.twig', [
            'hotel' => $hotel,
            'chambres' => $chambres,
        ]);
    }
    
    #[Route('/{idH}/chambres', name: 'app_hotel_chambres')]
    public function chambres(Connection $connection, int $idH): Response
    {
        $hotel = $connection->fetchAssociative("SELECT * FROM hotel WHERE idH = ?", [$idH]);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé');
        }
        
        $chambres = $connection->fetchAllAssociative("SELECT * FROM chambre WHERE idH = ? ORDER BY idCh ASC", [$idH]);
        
        return $this->render('hotel/chambres.html.twig', [
            'hotel' => $hotel,
            'chambres' => $chambres,
        ]);
    }
}