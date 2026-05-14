<?php

namespace App\Controller;

<<<<<<< HEAD
=======
use App\Entity\ReservationProg;
use App\Service\StripeService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
<<<<<<< HEAD
use App\Service\StripeService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ReservationProg;
use App\Entity\Programme;

class StripeController extends AbstractController
{
=======

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

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Route('/success', name: 'stripe_success')]
    public function success(
        Request $request,
        StripeService $stripeService,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
<<<<<<< HEAD
        $sessionId     = $request->query->get('session_id');
        $reservationId = $request->query->get('reservation_id');

        $sessionId     = is_string($sessionId) ? $sessionId : null;
        $reservationId = is_numeric($reservationId) ? (int) $reservationId : null;

=======
        $sessionId = $request->query->get('session_id');
        $reservationId = $request->query->get('reservation_id');
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        if (!$sessionId || !$reservationId) {
            $this->addFlash('error', 'Informations de paiement manquantes');
            return $this->redirectToRoute('app_voyage_index');
        }

        $paymentStatus = $stripeService->verifyPaymentStatus($sessionId);
<<<<<<< HEAD

        if ($paymentStatus['paid']) {
            $reservation = $entityManager->getRepository(ReservationProg::class)->find($reservationId);

            if ($reservation) {
                $reservation->setStatutPaiement('paye');
                $entityManager->flush();

                $programmeId = $reservation->getIdP();

                if ($programmeId) {
                    // FIX :49 — find() returns object|null typed as mixed in PHPDoc,
                    // so instanceof Programme was always true per PHPStan.
                    // Use a null-check + is_a() or simply drop the redundant instanceof
                    // and rely on the repository's return type alone.
                    $programme = $entityManager->getRepository(Programme::class)->find($programmeId);

                    if ($programme !== null) {
                        $clientEmail   = $reservation->getEmail();
                        $clientName    = trim(($reservation->getPrenom() ?? '') . ' ' . ($reservation->getNom() ?? ''));
                        $programmeNom  = $programme->getNom() ?? 'Programme';
                        $dateDebut     = $programme->getDateDebut();
                        $programmeDate = $dateDebut instanceof \DateTimeInterface ? $dateDebut->format('d/m/Y') : 'Date non spécifiée';
                        $programmeLieu = $programme->getLieu() ?? 'Lieu non spécifié';
                        $nbrePersonnes = $reservation->getNbre() ?? 1;
                        $prixTotal     = $reservation->getPrixProg() ?? 0.0;

                        if ($clientEmail) {
                            try {
                                $emailService->sendReservationConfirmation(
                                    $clientEmail,
                                    $clientName ?: 'Client',
                                    $programmeNom,
                                    $programmeDate,
                                    $programmeLieu,
                                    $nbrePersonnes,
                                    $prixTotal
                                );
                                $this->addFlash('success', '✅ Paiement effectué ! Un email de confirmation vous a été envoyé.');
                            } catch (\Exception $e) {
                                $this->addFlash('warning', '✅ Paiement effectué ! Mais l\'email de confirmation n\'a pas pu être envoyé.');
                            }
                        } else {
                            $this->addFlash('success', '✅ Paiement effectué avec succès ! Votre réservation est confirmée.');
                        }
                    } else {
                        $this->addFlash('warning', '✅ Paiement effectué, mais les détails du programme sont introuvables.');
                    }
                } else {
                    $this->addFlash('success', '✅ Paiement effectué avec succès ! Votre réservation est confirmée.');
                }
            } else {
                $this->addFlash('warning', 'Réservation non trouvée, mais le paiement a été effectué.');
=======
        
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
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            }
        } else {
            $this->addFlash('warning', 'Le paiement est en attente de confirmation.');
        }

        return $this->redirectToRoute('app_voyage_index');
    }
<<<<<<< HEAD
=======

    #[Route('/cancel', name: 'stripe_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé. Vous pouvez réessayer quand vous voulez.');
        return $this->redirectToRoute('app_voyage_index');
    }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
}