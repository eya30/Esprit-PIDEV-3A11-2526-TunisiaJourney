<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function sendReservationConfirmation(
        string $clientEmail,
        string $clientName,
        string $programmeNom,
        string $programmeDate,
        string $lieu,
        int $nbrePersonnes,
        float $prixTotal
    ): bool {
        try {
            $email = (new Email())
                ->from(new \Symfony\Component\Mime\Address('souhamzoughi01@gmail.com', 'TunisiaJourney'))
                ->to($clientEmail)
                ->subject('Confirmation de votre réservation - TunisiaJourney')
                ->html($this->getReservationEmailHtml(
                    $clientName, $programmeNom, $programmeDate, $lieu, $nbrePersonnes, $prixTotal, $clientEmail
                ));

            $this->mailer->send($email);
            $this->logger->info('Email envoyé à ' . $clientEmail);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendAdminNotification(
        string $clientName,
        string $clientEmail,
        string $clientPhone,
        string $programmeNom,
        int $nbrePersonnes,
        float $prixTotal
    ): bool {
        try {
            $adminEmail = (new Email())
                ->from(new \Symfony\Component\Mime\Address('souhamzoughi01@gmail.com', 'TunisiaJourney'))
                ->to('souhamzoughi01@gmail.com')
                ->subject('Nouvelle réservation - TunisiaJourney')
                ->html($this->getAdminNotificationHtml(
                    $clientName, $clientEmail, $clientPhone, $programmeNom, $nbrePersonnes, $prixTotal
                ));

            $this->mailer->send($adminEmail);
            $this->logger->info('Notification admin envoyée');
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email admin: ' . $e->getMessage());
            return false;
        }
    }

    private function getReservationEmailHtml(
        string $clientName,
        string $programmeNom,
        string $programmeDate,
        string $lieu,
        int $nbrePersonnes,
        float $prixTotal,
        string $clientEmail
    ): string {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Confirmation TunisiaJourney</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f5f5f5; line-height: 1.5; color: #1a1a1a; }
                .container { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
                .header { background: #212E53; padding: 28px 30px; text-align: center; }
                .logo { font-size: 24px; font-weight: 600; color: white; letter-spacing: 1px; }
                .logo span { color: #A7001E; }
                .content { padding: 35px 30px; }
                .greeting { font-size: 20px; font-weight: 500; color: #212E53; margin-bottom: 8px; }
                .subtitle { color: #666; font-size: 14px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
                .card { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
                .card-title { font-size: 14px; font-weight: 600; color: #A7001E; margin-bottom: 15px; }
                .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef; }
                .label { color: #666; font-size: 13px; }
                .value { color: #1a1a1a; font-weight: 500; font-size: 13px; }
                .total { background: #212E53; color: white; padding: 14px 18px; border-radius: 8px; margin-top: 18px; display: flex; justify-content: space-between; }
                .total-label { font-size: 13px; opacity: 0.85; }
                .total-amount { font-size: 20px; font-weight: 600; }
                .thankyou { text-align: center; margin: 25px 0 0; padding-top: 20px; border-top: 1px solid #eee; }
                .thankyou p { color: #212E53; font-size: 14px; font-weight: 500; }
                .footer { background: #f8f9fa; padding: 20px 30px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div style="background-color: #f5f5f5; padding: 40px 20px;">
                <div class="container">
                    <div class="header">
                        <div class="logo">Tunisia<span>Journey</span></div>
                    </div>
                    <div class="content">
                        <div class="greeting">Bonjour ' . htmlspecialchars($clientName) . ',</div>
                        <div class="subtitle">Votre réservation est confirmée</div>
                        <div class="card">
                            <div class="card-title">DÉTAILS DE VOTRE VOYAGE</div>
                            <div class="row"><span class="label">Programme</span><span class="value">' . htmlspecialchars($programmeNom) . '</span></div>
                            <div class="row"><span class="label">Lieu</span><span class="value">' . htmlspecialchars($lieu) . '</span></div>
                            <div class="row"><span class="label">Date</span><span class="value">' . htmlspecialchars($programmeDate) . '</span></div>
                            <div class="row"><span class="label">Voyageurs</span><span class="value">' . $nbrePersonnes . ' personne(s)</span></div>
                            <div class="total"><span class="total-label">Total</span><span class="total-amount">' . number_format($prixTotal, 2) . ' DT</span></div>
                        </div>
                        <div class="thankyou"><p>Merci de votre confiance.</p><p style="font-size: 12px; color: #666; margin-top: 5px;">L\'équipe TunisiaJourney</p></div>
                    </div>
                    <div class="footer"><p>TunisiaJourney - Agence de voyage</p><p>© ' . date('Y') . ' Tous droits réservés</p></div>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    private function getAdminNotificationHtml(
        string $clientName,
        string $clientEmail,
        string $clientPhone,
        string $programmeNom,
        int $nbrePersonnes,
        float $prixTotal
    ): string {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Nouvelle réservation</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; padding: 40px 20px; }
                .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
                .header { background: #212E53; padding: 20px; text-align: center; }
                .header h1 { color: white; font-size: 20px; font-weight: 500; }
                .header span { color: #A7001E; }
                .content { padding: 25px; }
                .badge { background: #A7001E; color: white; padding: 5px 12px; border-radius: 20px; display: inline-block; font-size: 11px; margin-bottom: 20px; }
                .card { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 15px 0; }
                .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
                .label { color: #666; font-size: 12px; }
                .value { color: #1a1a1a; font-weight: 500; font-size: 12px; }
                .total { background: #212E53; color: white; padding: 12px 15px; border-radius: 8px; margin-top: 15px; display: flex; justify-content: space-between; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 10px; color: #999; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header"><h1>Tunisia<span>Journey</span></h1></div>
                <div class="content">
                    <div class="badge">NOUVELLE RÉSERVATION</div>
                    <div class="card">
                        <div class="row"><span class="label">Client</span><span class="value">' . htmlspecialchars($clientName) . '</span></div>
                        <div class="row"><span class="label">Email</span><span class="value">' . htmlspecialchars($clientEmail) . '</span></div>
                        <div class="row"><span class="label">Téléphone</span><span class="value">' . htmlspecialchars($clientPhone) . '</span></div>
                        <div class="row"><span class="label">Programme</span><span class="value">' . htmlspecialchars($programmeNom) . '</span></div>
                        <div class="row"><span class="label">Personnes</span><span class="value">' . $nbrePersonnes . '</span></div>
                        <div class="total"><span>Montant total</span><span>' . number_format($prixTotal, 2) . ' DT</span></div>
                    </div>
                </div>
                <div class="footer">© ' . date('Y') . ' TunisiaJourney</div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}