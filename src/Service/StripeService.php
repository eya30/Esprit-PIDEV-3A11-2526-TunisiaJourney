<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ReservationProg;

class StripeService
{
    private string $secretKey;
    private string $publicKey;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        string $stripeSecretKey,
        string $stripePublicKey,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->secretKey = $stripeSecretKey;
        $this->publicKey = $stripePublicKey;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        
        Stripe::setApiKey($this->secretKey);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Crée une session de paiement Stripe
     */
    public function createCheckoutSession(ReservationProg $reservation, string $successUrl, string $cancelUrl): ?Session
    {
        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => 'Réservation - ' . $reservation->getPrenom() . ' ' . $reservation->getNom(),
                                'description' => 'Programme de voyage TunisiaJourney',
                            ],
                            'unit_amount' => (int)($reservation->getPrixProg() * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}&reservation_id=' . $reservation->getIdRP(),
                'cancel_url' => $cancelUrl . '?canceled=true',
                'metadata' => [
                    'reservation_id' => $reservation->getIdRP(),
                    'client_email' => $reservation->getEmail(),
                    'client_name' => $reservation->getPrenom() . ' ' . $reservation->getNom(),
                ],
            ]);

            $reservation->setStripeSessionId($session->id);
            $this->entityManager->flush();

            $this->logger->info('Session Stripe créée pour réservation ' . $reservation->getIdRP());
            
            return $session;

        } catch (\Exception $e) {
            $this->logger->error('Erreur création session Stripe: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie le statut d'un paiement
     */
    public function verifyPaymentStatus(string $sessionId): array
    {
        try {
            $session = Session::retrieve($sessionId);
            
            return [
                'status' => $session->payment_status,
                'paid' => $session->payment_status === 'paid',
                'amount' => $session->amount_total / 100,
                'currency' => $session->currency,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur vérification paiement: ' . $e->getMessage());
            return ['status' => 'error', 'paid' => false];
        }
    }
}