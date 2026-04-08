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
        $evenements = $connection->fetchAllAssociative("SELECT * FROM Evenement ORDER BY IDEv DESC LIMIT 6");
        return $this->render('accueil/index.html.twig', [
            'evenements' => $evenements,
        ]);
    }
}