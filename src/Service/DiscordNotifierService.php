<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DiscordNotifierService
{
<<<<<<< HEAD
    private Connection $connection;
    private LoggerInterface $logger;
    private string $webhookUrl;
=======
    private $connection;
    private $logger;
    private $webhookUrl;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        // URL directement ici
        $this->webhookUrl = 'https://discord.com/api/webhooks/1495389190291198065/HVAo0x5fLCSdCBXP53eBvcqAI7RDwIy8bB_ouPim2XizlD6v0MPiRT1-gazqKYVHHv_Z';
    }

<<<<<<< HEAD
    /**
     * Envoie une notification Discord pour une nouvelle réservation
     *
     * @param array{
     *     idRP: int,
     *     nom: string,
     *     prenom: string,
     *     telephone: string,
     *     email: string,
     *     nbre: int,
     *     prixProg?: float,
     *     dateProgramme: string,
     *     programme_nom?: string,
     *     voyage_nom?: string,
     *     voyage_id?: int
     * } $reservation
     * @return bool
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function notifyNewReservation(array $reservation): bool
    {
        if (!$this->webhookUrl) {
            $this->logger->warning('Discord Webhook URL non configuré');
            return false;
        }

        try {
            $message = $this->buildDiscordMessage($reservation);
<<<<<<< HEAD

            $ch = curl_init($this->webhookUrl);
            if ($ch === false) {
                $this->logger->error('Impossible d\'initialiser cURL pour Discord');
                return false;
            }

            $jsonPayload = json_encode(['content' => $message]);
            if ($jsonPayload === false) {
                $this->logger->error('Erreur d\'encodage JSON pour le message Discord');
                return false;
            }

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

=======
            
            $ch = curl_init($this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['content' => $message]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            if ($httpCode == 204) {
                $this->logger->info('✅ Notification Discord envoyée pour réservation #' . $reservation['idRP']);
                $this->saveNotificationToDatabase($reservation);
                return true;
            } else {
<<<<<<< HEAD
                $this->logger->error('❌ Erreur Discord HTTP ' . $httpCode . ' - Réponse: ' . (is_string($response) ? $response : 'vide'));
                return false;
            }

=======
                $this->logger->error('❌ Erreur Discord HTTP ' . $httpCode);
                return false;
            }
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur envoi Discord: ' . $e->getMessage());
            return false;
        }
    }

<<<<<<< HEAD
    /**
     * Construit le message Discord
     *
     * @param array{
     *     idRP: int,
     *     nom: string,
     *     prenom: string,
     *     telephone: string,
     *     email: string,
     *     nbre: int,
     *     prixProg?: float,
     *     dateProgramme: string,
     *     programme_nom?: string,
     *     voyage_nom?: string
     * } $reservation
     * @return string
     */
    private function buildDiscordMessage(array $reservation): string
    {
        $programmeNom = $reservation['programme_nom'] ?? 'Non spécifié';
        $voyageNom    = $reservation['voyage_nom'] ?? 'Non spécifié';
        $prix         = $reservation['prixProg'] ?? 0;

        $message  = "🔔 **NOUVELLE RÉSERVATION**\n";
=======
    private function buildDiscordMessage(array $reservation): string
    {
        $programmeNom = $reservation['programme_nom'] ?? 'Non spécifié';
        $voyageNom = $reservation['voyage_nom'] ?? 'Non spécifié';
        
        $message = "🔔 **NOUVELLE RÉSERVATION**\n";
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📌 ID: #" . $reservation['idRP'] . "\n";
        $message .= "👤 Client: " . strtoupper($reservation['nom']) . " " . ucfirst($reservation['prenom']) . "\n";
        $message .= "📞 Téléphone: " . $reservation['telephone'] . "\n";
        $message .= "✉️ Email: " . $reservation['email'] . "\n";
        $message .= "👥 Personnes: " . $reservation['nbre'] . "\n";
<<<<<<< HEAD
        $message .= "💰 Montant: " . number_format($prix, 0, ',', ' ') . " DT\n";

        $timestamp = strtotime($reservation['dateProgramme']);
        $message .= "📅 Date: " . ($timestamp !== false ? date('d/m/Y H:i', $timestamp) : $reservation['dateProgramme']) . "\n";

=======
        $message .= "💰 Montant: " . number_format($reservation['prixProg'] ?? 0, 0, ',', ' ') . " DT\n";
        $message .= "📅 Date: " . date('d/m/Y H:i', strtotime($reservation['dateProgramme'])) . "\n";
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🏖️ Programme: " . $programmeNom . "\n";
        $message .= "✈️ Voyage: " . $voyageNom . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⚡ **Action requise:** Contacter le client sous 24h";
<<<<<<< HEAD

        return $message;
    }

    /**
     * Sauvegarde la notification dans la base de données
     *
     * @param array{
     *     idRP: int,
     *     nom: string,
     *     prenom: string,
     *     nbre: int,
     *     voyage_id?: int,
     *     voyage_nom?: string
     * } $reservation
     * @return void
     */
    private function saveNotificationToDatabase(array $reservation): void
    {
        try {
            $voyageId  = $reservation['voyage_id'] ?? null;
            $voyageNom = $reservation['voyage_nom'] ?? 'Inconnu';
            $message   = "🆕 Nouvelle réservation #{$reservation['idRP']} - {$reservation['prenom']} {$reservation['nom']} ({$reservation['nbre']} pers)";

=======
        
        return $message;
    }

    private function saveNotificationToDatabase(array $reservation): void
    {
        try {
            $voyageId = $reservation['voyage_id'] ?? null;
            $voyageNom = $reservation['voyage_nom'] ?? 'Inconnu';
            
            $message = "🆕 Nouvelle réservation #{$reservation['idRP']} - {$reservation['prenom']} {$reservation['nom']} ({$reservation['nbre']} pers)";
            
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $this->connection->executeStatement(
                "INSERT INTO notifications (type, message, voyage_id, voyage_nom, lu, date_creation) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                ['reservation', $message, $voyageId, $voyageNom, 0]
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur sauvegarde notification: ' . $e->getMessage());
        }
    }

<<<<<<< HEAD
    /**
     * Teste la connexion à Discord
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        if (!$this->webhookUrl) {
            $this->logger->warning('Discord Webhook URL non configuré');
            return false;
        }

        try {
            $testMessage  = "✅ **Test de connexion Discord - TunisiaJourney**\n";
=======
    public function testConnection(): bool
    {
        try {
            $testMessage = "✅ **Test de connexion Discord - TunisiaJourney**\n";
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $testMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $testMessage .= "🔔 Le système de notification fonctionne correctement !\n";
            $testMessage .= "📅 Date du test: " . date('d/m/Y H:i:s') . "\n";
            $testMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $testMessage .= "🎉 Prêt à recevoir les notifications de réservation !";
<<<<<<< HEAD

            $ch = curl_init($this->webhookUrl);
            if ($ch === false) {
                $this->logger->error('Impossible d\'initialiser cURL pour le test Discord');
                return false;
            }

            $jsonPayload = json_encode(['content' => $testMessage]);
            if ($jsonPayload === false) {
                $this->logger->error('Erreur d\'encodage JSON pour le message de test');
                return false;
            }

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $success = $httpCode == 204;

            if ($success) {
                $this->logger->info('✅ Test de connexion Discord réussi');
            } else {
                $this->logger->error('❌ Test de connexion Discord échoué - HTTP ' . $httpCode);
            }

            return $success;

        } catch (\Exception $e) {
            $this->logger->error('Exception lors du test Discord: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour l'URL du webhook Discord
     *
     * @param string $webhookUrl
     * @return void
     */
    public function setWebhookUrl(string $webhookUrl): void
    {
        $this->webhookUrl = $webhookUrl;
        $this->logger->info('URL du webhook Discord mise à jour');
    }

    /**
     * Obtient l'URL actuelle du webhook
     *
     * @return string
     */
    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }
=======
            
            $ch = curl_init($this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['content' => $testMessage]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode == 204;
        } catch (\Exception $e) {
            return false;
        }
    }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
}