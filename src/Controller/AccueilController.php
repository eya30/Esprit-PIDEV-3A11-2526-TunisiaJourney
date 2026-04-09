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
        $voyages = $connection->fetchAllAssociative("SELECT * FROM voyages ORDER BY idV DESC LIMIT 6");
        return $this->render('accueil/index.html.twig', [
            'voyages' => $voyages,
        ]);
    }

    
}