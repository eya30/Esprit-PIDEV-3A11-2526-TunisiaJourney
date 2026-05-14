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

<<<<<<< HEAD
    public function sendSms(?string $to, string $message): bool
    {
        // Vérifier que le numéro n'est pas nul ou vide
        if ($to === null || trim($to) === '') {
            $this->logger->error("Numéro de téléphone invalide (null ou vide)");
            return false;
        }
       
        // Nettoyer le numéro
        $toClean = preg_replace('/[^0-9+]/', '', $to);
       
        // Vérifier que le nettoyage a donné un résultat valide
        if ($toClean === null || $toClean === '') {
            $this->logger->error("Numéro de téléphone invalide après nettoyage");
            return false;
        }
       
        // Ajouter +216 si nécessaire (pour Tunisie)
        if (!str_starts_with($toClean, '+') && strlen($toClean) === 8) {
            $toClean = '+216' . $toClean;
        }
       
        try {
            $this->client->messages->create($toClean, [
                'from' => $this->twilioPhoneNumber,
                'body' => $message
            ]);
           
            $this->logger->info("SMS envoyé à {$toClean}");
            return true;
           
=======
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
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
