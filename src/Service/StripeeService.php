<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeeService
{
    private string $secretKey;
    private string $publicKey;

    public function __construct(string $secretKey, string $publicKey)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
        Stripe::setApiKey($this->secretKey);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function createCheckoutSession(array $items, string $successUrl, string $cancelUrl): Session
    {
        $lineItems = [];

        foreach ($items as $item) {
            // ✅ Stripe ne supporte pas TND — conversion TND → EUR (1 TND ≈ 0.30 EUR)
            // On utilise EUR et on convertit le prix
            $prixEur = round($item['price'] * 0.30, 2);
            $amountCents = intval($prixEur * 100); // Stripe attend des centimes

            // Minimum 50 centimes
            if ($amountCents < 50) {
                $amountCents = 50;
            }

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur', // ✅ EUR supporté par Stripe
                    'product_data' => [
                        'name'        => $item['name'],
                        'description' => sprintf(
                            '%s — Prix original : %.2f TND',
                            $item['description'] ?? 'Produit TunisiaJourney',
                            $item['price']
                        ),
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => $item['quantity'],
            ];
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'locale'               => 'fr',
        ]);
    }
}