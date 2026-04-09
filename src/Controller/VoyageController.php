<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/voyage')]
class VoyageController extends AbstractController
{
    #[Route('/', name: 'app_voyage_index')]
    public function index(Connection $connection): Response
    {
        $voyages = $connection->fetchAllAssociative("SELECT * FROM voyages ORDER BY idV DESC");
        
        return $this->render('voyage/index.html.twig', [
            'voyages' => $voyages,
        ]);
    }
    
    #[Route('/{idV}', name: 'app_voyage_show')]
    public function show(Connection $connection, int $idV): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$idV]);
        
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage non trouvé');
        }
        
        $programmes = $connection->fetchAllAssociative("SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC", [$idV]);
        
        return $this->render('voyage/show.html.twig', [
            'voyage' => $voyage,
            'programmes' => $programmes,
        ]);
    }
    
    #[Route('/{idV}/programmes', name: 'app_voyage_programmes')]
    public function programmes(Connection $connection, int $idV): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$idV]);
        
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage non trouvé');
        }
        
        $programmes = $connection->fetchAllAssociative("SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC", [$idV]);
        
        return $this->render('voyage/programmes.html.twig', [
            'voyage' => $voyage,
            'programmes' => $programmes,
        ]);
    }
}