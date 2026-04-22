<?php
// src/Service/SmsService.php

namespace App\Service;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class SmsService
{
    private Client $client;
    private string $twilioPhoneNumber;
    private LoggerInterface $logger;

    public function __construct(string $accountSid, string $authToken, string $twilioPhoneNumber, LoggerInterface $logger)
    {
        $this->client = new Client($accountSid, $authToken);
        $this->twilioPhoneNumber = $twilioPhoneNumber;
        $this->logger = $logger;
    }

    public function sendSms(string $to, string $message): bool
    {
        // Nettoyer le numéro
        $to = preg_replace('/[^0-9+]/', '', $to);
        
        // Ajouter +216 si nécessaire (pour Tunisie)
        if (!str_starts_with($to, '+') && strlen($to) === 8) {
            $to = '+216' . $to;
        }
        
        try {
            $this->client->messages->create($to, [
                'from' => $this->twilioPhoneNumber,
                'body' => $message
            ]);
            
            $this->logger->info("SMS envoyé à {$to}");
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur SMS: " . $e->getMessage());
            return false;
        }
    }

    public function generateConfirmationMessage(string $hotelNom, string $dateDebut, string $dateFin, int $nbNuits, float $prixTotal): string
    {
        return "✅ Réservation confirmée !\n" .
               "🏨 Hôtel: {$hotelNom}\n" .
               "📅 Du: {$dateDebut} au {$dateFin}\n" .
               "🌙 Nuits: {$nbNuits}\n" .
               "💰 Total: {$prixTotal} TND\n" .
               "Merci pour votre confiance !";
    }
}