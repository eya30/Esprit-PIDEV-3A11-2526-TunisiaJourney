<?php
// src/Controller/Admin/AdminNotificationChController.php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/notifications/chambres')]
class AdminNotificationChController extends AbstractController
{
    #[Route('/', name: 'app_admin_notifications_chambres')]
    public function index(Connection $connection): Response
    {
        // Avis négatifs non lus (sans JOIN sur reservation_chambre)
        $avisNegatifs = $connection->fetchAllAssociative(
            "SELECT a.*, u.prenom, u.nom, u.email
             FROM avis_chambre a
             JOIN utilisateur u ON a.utilisateur_id = u.id
             WHERE a.sentiment = 'negatif'
             AND a.statut_notification = 'non_lue'
             ORDER BY a.date_creation DESC"
        );
        
        // Avis négatifs déjà lus
        $avisLus = $connection->fetchAllAssociative(
            "SELECT a.*, u.prenom, u.nom, u.email
             FROM avis_chambre a
             JOIN utilisateur u ON a.utilisateur_id = u.id
             WHERE a.sentiment = 'negatif'
             AND a.statut_notification = 'lue'
             ORDER BY a.date_creation DESC
             LIMIT 50"
        );
        
        // Statistiques
        $stats = $connection->fetchAllAssociative(
            "SELECT sentiment, COUNT(*) as total 
             FROM avis_chambre 
             WHERE sentiment IS NOT NULL 
             GROUP BY sentiment"
        );
        
        $totalNegatifs = $connection->fetchOne(
            "SELECT COUNT(*) FROM avis_chambre WHERE sentiment = 'negatif' AND statut_notification = 'non_lue'"
        );
        
        return $this->render('admin/notifications_chambres.html.twig', [
            'avisNegatifs' => $avisNegatifs,
            'avisLus' => $avisLus,
            'totalNegatifs' => $totalNegatifs,
            'stats' => $stats,
        ]);
    }
    
    #[Route('/marquer-lue/{id}', name: 'app_admin_marquer_lue_chambre')]
    public function marquerLue(int $id, Connection $connection): Response
    {
        $connection->update('avis_chambre', 
            ['statut_notification' => 'lue'],
            ['id' => $id]
        );
        
        $this->addFlash('success', 'Notification marquée comme lue.');
        return $this->redirectToRoute('app_admin_notifications_chambres');
    }
}