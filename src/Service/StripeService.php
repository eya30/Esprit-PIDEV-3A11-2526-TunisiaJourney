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
     * 
     * @param ReservationProg $reservation La réservation à payer
     * @param string $successUrl URL de redirection en cas de succès
     * @param string $cancelUrl URL de redirection en cas d'annulation
     * @return Session|null La session Stripe créée ou null en cas d'erreur
     */
    public function createCheckoutSession(ReservationProg $reservation, string $successUrl, string $cancelUrl): ?Session
    {
        try {
            $reservationId = $reservation->getIdRP();
            $clientEmail = $reservation->getEmail();
            $clientName = trim($reservation->getPrenom() . ' ' . $reservation->getNom());
            
            if (empty($clientName)) {
                $clientName = 'Client';
            }
            
            if (empty($clientEmail)) {
                $clientEmail = 'client@example.com';
            }
            
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => 'Réservation - ' . $clientName,
                                'description' => 'Programme de voyage TunisiaJourney',
                            ],
                            'unit_amount' => (int)($reservation->getPrixProg() * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}&reservation_id=' . $reservationId,
                'cancel_url' => $cancelUrl . '?canceled=true',
                'metadata' => [
                    'reservation_id' => (string)$reservationId,
                    'client_email' => $clientEmail,
                    'client_name' => $clientName,
                ],
            ]);

            // Correction: session n'est jamais null, suppression de !== null
            if (isset($session->id)) {
                $reservation->setStripeSessionId($session->id);
                $this->entityManager->flush();
                $this->logger->info('Session Stripe créée pour réservation ' . $reservationId);
            }
            
            return $session;

        } catch (\Exception $e) {
            $this->logger->error('Erreur création session Stripe: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie le statut d'un paiement
     * 
     * @param string $sessionId L'ID de la session Stripe
     * @return array{status: string, paid: bool, amount?: float, currency?: string}
     */
    public function verifyPaymentStatus(string $sessionId): array
    {
        try {
            $session = Session::retrieve($sessionId);
            
            $paymentStatus = $session->payment_status ?? 'unknown';
            
            $result = [
                'status' => $paymentStatus,
                'paid' => $paymentStatus === 'paid',
            ];
            
            if (isset($session->amount_total)) {
                $result['amount'] = $session->amount_total / 100;
            }
            
            if (isset($session->currency)) {
                $result['currency'] = $session->currency;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur vérification paiement pour session ' . $sessionId . ': ' . $e->getMessage());
            return [
                'status' => 'error',
                'paid' => false,
            ];
        }
    }

    /**
     * Annule une session de paiement
     * 
     * @param string $sessionId L'ID de la session Stripe
     * @return bool True si l'annulation a réussi, false sinon
     */
    public function cancelCheckoutSession(string $sessionId): bool
    {
        try {
            $session = Session::retrieve($sessionId);
            
            $paymentStatus = $session->payment_status ?? '';
            
            // Correction: suppression de !== null car toujours true
            if ($paymentStatus !== 'paid') {
                $session->expire(); // expire() existe toujours sur l'objet Session
                $this->logger->info('Session Stripe expirée: ' . $sessionId);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur annulation session Stripe: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les détails d'une session
     * 
     * @param string $sessionId L'ID de la session Stripe
     * @return array<string, mixed>|null Les détails de la session ou null en cas d'erreur
     */
    public function getSessionDetails(string $sessionId): ?array
    {
        try {
            $session = Session::retrieve($sessionId);
            
            // Correction: suppression du instanceof StripeObject car toujours true
            $metadata = [];
            if (isset($session->metadata)) {
                $metadata = $session->metadata->toArray();
            }
            
            $customerEmail = null;
            $customerName = null;
            if (isset($session->customer_details)) {
                $customerEmail = $session->customer_details->email ?? null;
                $customerName = $session->customer_details->name ?? null;
            }
            
            return [
                'id' => $session->id ?? null,
                'payment_status' => $session->payment_status ?? null,
                'amount_total' => isset($session->amount_total) ? $session->amount_total / 100 : 0,
                'currency' => $session->currency ?? 'eur',
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
                'metadata' => $metadata,
                'created' => isset($session->created) ? date('Y-m-d H:i:s', $session->created) : date('Y-m-d H:i:s'),
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération détails session: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si une réservation a déjà une session Stripe valide
     * 
     * @param ReservationProg $reservation La réservation à vérifier
     * @return bool True si la session est valide, false sinon
     */
    public function hasValidSession(ReservationProg $reservation): bool
    {
        $sessionId = $reservation->getStripeSessionId();
        
        if (empty($sessionId)) {
            return false;
        }
        
        try {
            $session = Session::retrieve($sessionId);
            // Correction: suppression de !== null car toujours true
            $paymentStatus = $session->payment_status ?? '';
            return $paymentStatus !== 'paid';
            
        } catch (\Exception $e) {
            $this->logger->warning('Session Stripe invalide pour réservation ' . $reservation->getIdRP());
            return false;
        }
    }

    /**
     * Formate un montant pour Stripe (en centimes)
     * 
     * @param float $amount Le montant en unité monétaire
     * @return int Le montant en centimes
     */
    public function formatAmountForStripe(float $amount): int
    {
        return (int)round($amount * 100);
    }

    /**
     * Formate un montant depuis Stripe (en unité monétaire)
     * 
     * @param int $amount Le montant en centimes
     * @return float Le montant en unité monétaire
     */
    public function formatAmountFromStripe(int $amount): float
    {
        return $amount / 100;
    }
}