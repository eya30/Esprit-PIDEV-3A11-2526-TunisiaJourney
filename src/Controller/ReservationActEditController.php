<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationActEditController extends AbstractController
{
    #[Route('/evenement/reservation/{id}/edit', name: 'app_reservation_act_edit_page', methods: ['GET'])]
    public function editPage(Connection $connection, int $id): Response
    {
        $reservation = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = ?", [$id]
        );

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        // ── NOUVEAU : Empêcher la modification d'une réservation annulée ──
        if (isset($reservation['status']) && $reservation['status'] === 'annulé') {
            $this->addFlash('error', 'Impossible de modifier une réservation annulée.');
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/editreservationAct.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}
