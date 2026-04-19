<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DiscordNotifierService
{
    private $connection;
    private $logger;
    private $webhookUrl;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        // URL directement ici
        $this->webhookUrl = 'https://discord.com/api/webhooks/1495389190291198065/HVAo0x5fLCSdCBXP53eBvcqAI7RDwIy8bB_ouPim2XizlD6v0MPiRT1-gazqKYVHHv_Z';
    }

    public function notifyNewReservation(array $reservation): bool
    {
        if (!$this->webhookUrl) {
            $this->logger->warning('Discord Webhook URL non configuré');
            return false;
        }

        try {
            $message = $this->buildDiscordMessage($reservation);
            
            $ch = curl_init($this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['content' => $message]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 204) {
                $this->logger->info('✅ Notification Discord envoyée pour réservation #' . $reservation['idRP']);
                $this->saveNotificationToDatabase($reservation);
                return true;
            } else {
                $this->logger->error('❌ Erreur Discord HTTP ' . $httpCode);
                return false;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur envoi Discord: ' . $e->getMessage());
            return false;
        }
    }

    private function buildDiscordMessage(array $reservation): string
    {
        $programmeNom = $reservation['programme_nom'] ?? 'Non spécifié';
        $voyageNom = $reservation['voyage_nom'] ?? 'Non spécifié';
        
        $message = "🔔 **NOUVELLE RÉSERVATION**\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📌 ID: #" . $reservation['idRP'] . "\n";
        $message .= "👤 Client: " . strtoupper($reservation['nom']) . " " . ucfirst($reservation['prenom']) . "\n";
        $message .= "📞 Téléphone: " . $reservation['telephone'] . "\n";
        $message .= "✉️ Email: " . $reservation['email'] . "\n";
        $message .= "👥 Personnes: " . $reservation['nbre'] . "\n";
        $message .= "💰 Montant: " . number_format($reservation['prixProg'] ?? 0, 0, ',', ' ') . " DT\n";
        $message .= "📅 Date: " . date('d/m/Y H:i', strtotime($reservation['dateProgramme'])) . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🏖️ Programme: " . $programmeNom . "\n";
        $message .= "✈️ Voyage: " . $voyageNom . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⚡ **Action requise:** Contacter le client sous 24h";
        
        return $message;
    }

    private function saveNotificationToDatabase(array $reservation): void
    {
        try {
            $voyageId = $reservation['voyage_id'] ?? null;
            $voyageNom = $reservation['voyage_nom'] ?? 'Inconnu';
            
            $message = "🆕 Nouvelle réservation #{$reservation['idRP']} - {$reservation['prenom']} {$reservation['nom']} ({$reservation['nbre']} pers)";
            
            $this->connection->executeStatement(
                "INSERT INTO notifications (type, message, voyage_id, voyage_nom, lu, date_creation) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                ['reservation', $message, $voyageId, $voyageNom, 0]
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur sauvegarde notification: ' . $e->getMessage());
        }
    }

    public function testConnection(): bool
    {
        try {
            $testMessage = "✅ **Test de connexion Discord - TunisiaJourney**\n";
            $testMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $testMessage .= "🔔 Le système de notification fonctionne correctement !\n";
            $testMessage .= "📅 Date du test: " . date('d/m/Y H:i:s') . "\n";
            $testMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $testMessage .= "🎉 Prêt à recevoir les notifications de réservation !";
            
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
}