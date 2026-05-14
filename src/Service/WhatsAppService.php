<?php
// src/Service/WhatsAppService.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'envoi de messages WhatsApp via Twilio API.
 *
 * Variables .env requises :
 *   TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 *   TWILIO_AUTH_TOKEN=your_auth_token
 *   TWILIO_WHATSAPP_NUMBER=+14155238886   (sandbox Twilio)
 */
class WhatsAppService
{
<<<<<<< HEAD
=======
    // URL de l'API Twilio Messages
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private const TWILIO_API_URL = 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json';

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface     $logger,
<<<<<<< HEAD
        private string              $accountSid,
        private string              $authToken,
        private string              $fromNumber,
    ) {}

    /**
     * @return array{success: bool, sid: string|null, error: string|null}
     */
    public function send(string $toPhone, string $message): array
    {
=======
        private string              $accountSid,    // injecté via services.yaml
        private string              $authToken,
        private string              $fromNumber,    // ex: whatsapp:+14155238886
    ) {}

    // ════════════════════════════════════════════════════
    //  Envoie un message WhatsApp
    //
    //  @param string $toPhone   Numéro au format +21612345678
    //  @param string $message   Texte à envoyer
    //  @return array            ['success' => bool, 'sid' => string|null, 'error' => string|null]
    // ════════════════════════════════════════════════════
    public function send(string $toPhone, string $message): array
    {
        // Formater le numéro destination au format WhatsApp Twilio
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $toFormatted = $this->formatNumber($toPhone);

        if (!$toFormatted) {
            $this->logger->error('[WhatsApp] Numéro invalide : ' . $toPhone);
            return ['success' => false, 'sid' => null, 'error' => 'Numéro de téléphone invalide'];
        }

        $url = sprintf(self::TWILIO_API_URL, $this->accountSid);

        try {
            $response = $this->client->request('POST', $url, [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'body'       => [
<<<<<<< HEAD
                    'From' => $this->fromNumber,
=======
                    'From' => $this->fromNumber,        // whatsapp:+14155238886
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                    'To'   => 'whatsapp:' . $toFormatted,
                    'Body' => $message,
                ],
            ]);

<<<<<<< HEAD
            $data       = $response->toArray(false);
=======
            $data       = $response->toArray(false); // false = ne pas lancer d'exception sur erreur HTTP
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $statusCode = $response->getStatusCode();

            if ($statusCode === 201) {
                $this->logger->info('[WhatsApp] Message envoyé à ' . $toFormatted . ' — SID: ' . ($data['sid'] ?? '?'));
                return ['success' => true, 'sid' => $data['sid'] ?? null, 'error' => null];
            }

<<<<<<< HEAD
=======
            // Erreur Twilio (ex: numéro non inscrit au sandbox)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $errorMsg = $data['message'] ?? ('HTTP ' . $statusCode);
            $this->logger->warning('[WhatsApp] Échec envoi : ' . $errorMsg);
            return ['success' => false, 'sid' => null, 'error' => $errorMsg];

        } catch (\Throwable $e) {
            $this->logger->error('[WhatsApp] Exception : ' . $e->getMessage());
            return ['success' => false, 'sid' => null, 'error' => $e->getMessage()];
        }
    }

<<<<<<< HEAD
    /**
     * @return array{success: bool, sid: string|null, error: string|null}
     */
=======
    // ════════════════════════════════════════════════════
    //  Message "livreur arrivé" personnalisé
    // ════════════════════════════════════════════════════
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function sendLivreurArrive(string $toPhone, string $clientNom, string $clientPrenom, int $commandeId): array
    {
        $message = "🚚 *TunisiaJourney — Livraison*\n\n"
            . "Bonjour *{$clientPrenom} {$clientNom}*,\n\n"
            . "Votre livreur est arrivé à votre adresse pour la commande *#{$commandeId}*.\n\n"
            . "Merci de vous préparer à réceptionner votre colis. 📦\n\n"
            . "_Merci de votre confiance !_";

        return $this->send($toPhone, $message);
    }

<<<<<<< HEAD
    /**
     * @return array{success: bool, sid: string|null, error: string|null}
     */
    public function notifierLivraison(string $telephone, string $nomClient, int $commandeId, string $adresse): array
    {
        $message = "✅ *TunisiaJourney — Livraison confirmée*\n\n"
            . "Bonjour *{$nomClient}*,\n\n"
            . "Votre commande *#{$commandeId}* a été livrée à l'adresse :\n"
            . "{$adresse}\n\n"
            . "Merci de votre confiance ! 🎉\n"
            . "_L'équipe TunisiaJourney_";

        return $this->send($telephone, $message);
    }

    /**
     * @return array{success: bool, sid: string|null, error: string|null}
     */
    public function notifierEnRoute(string $telephone, string $nomClient, int $commandeId): array
    {
        $message = "🚚 *TunisiaJourney — Votre commande est en route*\n\n"
            . "Bonjour *{$nomClient}*,\n\n"
            . "Votre commande *#{$commandeId}* est actuellement en cours de livraison.\n"
            . "Vous pouvez suivre son trajet en temps réel sur notre site.\n\n"
            . "Merci de votre patience !";

        return $this->send($telephone, $message);
    }

    /**
     * @return array{success: bool, sid: string|null, error: string|null}
     */
    public function sendWhatsApp(string $telephone, string $message): array
    {
        return $this->send($telephone, $message);
    }

    private function formatNumber(string $phone): ?string
    {
=======
    // ════════════════════════════════════════════════════
    //  Formate le numéro tunisien en E.164 (+216XXXXXXXX)
    //  Accepte : 12345678, 0021612345678, +21612345678
    // ════════════════════════════════════════════════════
    private function formatNumber(string $phone): ?string
    {
        // Supprimer espaces, tirets, parenthèses
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $clean = preg_replace('/[\s\-\(\)\.]+/', '', $phone);

        if (!$clean) return null;

<<<<<<< HEAD
=======
        // Déjà en E.164 avec + ?
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        if (str_starts_with($clean, '+')) {
            return preg_match('/^\+\d{8,15}$/', $clean) ? $clean : null;
        }

<<<<<<< HEAD
=======
        // Préfixe international sans + (ex: 0021612345678)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        if (str_starts_with($clean, '00216')) {
            $local = substr($clean, 5);
            return strlen($local) === 8 ? '+216' . $local : null;
        }

<<<<<<< HEAD
=======
        // Numéro tunisien local 8 chiffres (ex: 12345678)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        if (preg_match('/^\d{8}$/', $clean)) {
            return '+216' . $clean;
        }

<<<<<<< HEAD
=======
        // Autre format international (déjà avec indicatif sans 00)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        if (preg_match('/^\d{10,15}$/', $clean)) {
            return '+' . $clean;
        }

        return null;
    }
<<<<<<< HEAD
}
=======
    public function notifierLivraison(string $telephone, string $nomClient, int $commandeId, string $adresse): array
{
    $message = "✅ *TunisiaJourney — Livraison confirmée*\n\n"
        . "Bonjour *{$nomClient}*,\n\n"
        . "Votre commande *#{$commandeId}* a été livrée à l'adresse :\n"
        . "{$adresse}\n\n"
        . "Merci de votre confiance ! 🎉\n"
        . "_L'équipe TunisiaJourney_";
    return $this->send($telephone, $message);
}
public function notifierEnRoute(string $telephone, string $nomClient, int $commandeId): array
{
    $message = "🚚 *TunisiaJourney — Votre commande est en route*\n\n"
        . "Bonjour *{$nomClient}*,\n\n"
        . "Votre commande *#{$commandeId}* est actuellement en cours de livraison.\n"
        . "Vous pouvez suivre son trajet en temps réel sur notre site.\n\n"
        . "Merci de votre patience !";
    return $this->send($telephone, $message);
}
public function sendWhatsApp(string $telephone, string $message): array
{
    return $this->send($telephone, $message);
}

}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
