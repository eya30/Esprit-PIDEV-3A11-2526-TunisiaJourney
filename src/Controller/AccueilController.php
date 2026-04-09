<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function index(Connection $connection): Response
    {
        // Récupérer les 6 derniers hôtels
        $hotels = $connection->fetchAllAssociative("SELECT * FROM hotel ORDER BY idH DESC LIMIT 6");
        
        return $this->render('accueil/index.html.twig', [
            'hotels' => $hotels,
        ]);
    }
}