<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationProgController extends AbstractController
{
    #[Route('/new/{idProg}', name: 'app_reservation_new', methods: ['POST'])]
    public function new(Request $request, Connection $connection, string $idProg): Response
    {
        $data = $request->request->all();
        
        // Récupérer le programme et le voyage associé
        $programme = $connection->fetchAssociative("SELECT * FROM programmes WHERE idProg = ?", [$idProg]);
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$programme['idV']]);
        
        // Calculer le prix total
        $prixTotal = $voyage['prix'] * $data['nbre'];
        
        // Insérer la réservation
        $connection->executeStatement(
            "INSERT INTO reservationprog (nom, prenom, telephone, nbre, prixProg, idP, dateProgramme, email, statutPaiement, user_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['nom'],
                $data['prenom'],
                $data['telephone'],
                $data['nbre'],
                $prixTotal,
                $idProg,
                date('Y-m-d'),
                $data['email'],
                'en_attente',
                null
            ]
        );
        
        $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');
        return $this->redirectToRoute('app_voyage_programmes', ['idV' => $programme['idV']]);
    }
}