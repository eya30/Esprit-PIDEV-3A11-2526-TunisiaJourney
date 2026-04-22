<?php
// src/Controller/Admin/NotificationApiController.php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class NotificationApiController extends AbstractController
{
    #[Route('/notifications/chambres', name: 'api_notifications_chambres')]
    public function getNotifications(Connection $connection): JsonResponse
    {
        $notifications = $connection->fetchAllAssociative(
            "SELECT a.*, u.prenom, u.nom
             FROM avis_chambre a
             JOIN utilisateur u ON a.utilisateur_id = u.id
             WHERE a.sentiment = 'negatif'
             AND a.statut_notification = 'non_lue'
             ORDER BY a.date_creation DESC
             LIMIT 10"
        );
        
        $total = count($notifications);
        
        return $this->json([
            'notifications' => $notifications,
            'total' => $total
        ]);
    }
}