<?php

namespace App\Service;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;
use Twilio\Exceptions\RestException;

class SmsActService
{
    private Client $client;
    private string $twilioPhoneNumber;
    private LoggerInterface $logger;

    public function __construct(
        string $accountSid,
        string $authToken,
        string $twilioPhoneNumber,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->logger->info("=== SMS SERVICE INITIALISÉ ===");
        $this->logger->info("Account SID: " . substr($accountSid, 0, 6) . "...");
        $this->logger->info("From Number: " . $twilioPhoneNumber);

        try {
            $this->client = new Client($accountSid, $authToken);
            $this->twilioPhoneNumber = $twilioPhoneNumber;
            $this->logger->info("✅ Client Twilio créé avec succès");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur création client Twilio: " . $e->getMessage());
            throw $e;
        }
    }

    public function sendSms(string $to, string $message): bool
    {
        $this->logger->info("=== ENVOI SMS ===");
        $this->logger->info("Destinataire original: " . $to);
        $this->logger->info("From: " . $this->twilioPhoneNumber);

        $originalTo = $to;

        // Étape 1 : supprimer tout sauf chiffres et +
        $to = (string) preg_replace('/[^0-9+]/', '', $to);

        // Étape 2 : si pas de code pays → ajouter +216 (Tunisie)
        if (!str_starts_with($to, '+')) {
            $to = '+216' . $to;
        }

        $this->logger->info("📞 Numéro formaté final: " . $to);

        try {
            $result = $this->client->messages->create($to, [
                'from' => $this->twilioPhoneNumber,
                'body' => $message
            ]);

            $this->logger->info("✅ SMS envoyé avec succès!");
            $this->logger->info("📊 SID: " . $result->sid);
            $this->logger->info("📊 Status: " . $result->status);
            return true;

        } catch (RestException $e) {
            $this->logger->error("❌ Twilio RestException: " . $e->getMessage());
            $this->logger->error("📊 Code: " . $e->getCode());
            $this->logger->error("📊 More info: " . $e->getMoreInfo());
            $this->logger->error("📊 Status: " . $e->getStatusCode());

            if ($e->getCode() == 21211) {
                $this->logger->error("💡 Le numéro '$originalTo' n'est pas valide ou n'est pas vérifié dans Twilio");
            } elseif ($e->getCode() == 21610) {
                $this->logger->error("💡 Compte Twilio en mode trial. Seuls les numéros vérifiés peuvent recevoir des SMS.");
            } elseif ($e->getCode() == 20003) {
                $this->logger->error("💡 Authentification Twilio échouée. Vérifie ton Account SID et Auth Token.");
            }
            return false;

        } catch (\Exception $e) {
            $this->logger->error("❌ Exception générale: " . $e->getMessage());
            $this->logger->error("📊 Type: " . get_class($e));
            return false;
        }
    }

    public function generatePlaceMessage(string $confirmationUrl): string
    {
        return "🎉 Une place s'est libérée !\n" .
               "Confirmez votre présence ici :\n" .
               $confirmationUrl . "\n" .
               "⚠️ Délai : 2 heures";
    }
}