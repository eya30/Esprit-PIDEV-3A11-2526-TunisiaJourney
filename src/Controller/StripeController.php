<?php

namespace App\Controller;

use App\Entity\ReservationProg;
use App\Service\StripeService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/paiement')]
class StripeController extends AbstractController
{
    #[Route('/checkout/{idReservation}', name: 'stripe_checkout')]
    public function checkout(
        int $idReservation,
        StripeService $stripeService,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $entityManager->getRepository(ReservationProg::class)->find($idReservation);
        
        if (!$reservation) {
            $this->addFlash('error', 'Réservation non trouvée');
            return $this->redirectToRoute('app_voyage_index');
        }

        $session = $stripeService->createCheckoutSession(
            $reservation,
            $this->generateUrl('stripe_success', [], 0),
            $this->generateUrl('stripe_cancel', [], 0)
        );

        if (!$session) {
            $this->addFlash('error', 'Erreur lors de l\'initialisation du paiement');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $reservation->getIdP()]);
        }

        return $this->redirect($session->url, 303);
    }

    #[Route('/success', name: 'stripe_success')]
    public function success(
        Request $request,
        StripeService $stripeService,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        $sessionId = $request->query->get('session_id');
        $reservationId = $request->query->get('reservation_id');
        
        if (!$sessionId || !$reservationId) {
            $this->addFlash('error', 'Informations de paiement manquantes');
            return $this->redirectToRoute('app_voyage_index');
        }

        $paymentStatus = $stripeService->verifyPaymentStatus($sessionId);
        
        if ($paymentStatus['paid']) {
            $reservation = $entityManager->getRepository(ReservationProg::class)->find($reservationId);
            
            if ($reservation) {
                $reservation->setStatutPaiement('paye');
                $entityManager->flush();
                
                // Récupérer le programme et le voyage
                $programme = $reservation->getIdP();
                if (is_object($programme)) {
                    $voyage = $programme->getVoyage();
                    
                    $emailService->sendReservationConfirmation(
                        $reservation->getEmail(),
                        $reservation->getPrenom() . ' ' . $reservation->getNom(),
                        $programme->getNom(),
                        $programme->getDateDebut()->format('d/m/Y'),
                        $programme->getLieu(),
                        $reservation->getNbre(),
                        $reservation->getPrixProg()
                    );
                }
                
                $this->addFlash('success', '✅ Paiement effectué avec succès ! Votre réservation est confirmée.');
            }
        } else {
            $this->addFlash('warning', 'Le paiement est en attente de confirmation.');
        }

        return $this->redirectToRoute('app_voyage_index');
    }

    #[Route('/cancel', name: 'stripe_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé. Vous pouvez réessayer quand vous voulez.');
        return $this->redirectToRoute('app_voyage_index');
    }
}