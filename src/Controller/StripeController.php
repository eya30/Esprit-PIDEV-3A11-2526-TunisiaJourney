<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\StripeService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ReservationProg;
use App\Entity\Programme;

class StripeController extends AbstractController
{
    #[Route('/success', name: 'stripe_success')]
    public function success(
        Request $request,
        StripeService $stripeService,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        $sessionId     = $request->query->get('session_id');
        $reservationId = $request->query->get('reservation_id');

        $sessionId     = is_string($sessionId) ? $sessionId : null;
        $reservationId = is_numeric($reservationId) ? (int) $reservationId : null;

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
            }
        } else {
            $this->addFlash('warning', 'Le paiement est en attente de confirmation.');
        }

        return $this->redirectToRoute('app_voyage_index');
    }
}