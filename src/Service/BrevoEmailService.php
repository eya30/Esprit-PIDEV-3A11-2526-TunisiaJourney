<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BrevoEmailService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = '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';
        $this->senderEmail = 'souhamzoughi01@gmail.com';
        $this->senderName = 'TunisiaJourney';
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
            $htmlContent = $this->getReservationEmailHtml(
                $clientName, $programmeNom, $programmeDate, $lieu, $nbrePersonnes, $prixTotal, $clientEmail
            );

            $data = [
                'sender' => [
                    'name' => $this->senderName,
                    'email' => $this->senderEmail
                ],
                'to' => [
                    [
                        'email' => $clientEmail,
                        'name' => $clientName
                    ]
                ],
                'subject' => 'Confirmation de votre reservation - TunisiaJourney',
                'htmlContent' => $htmlContent
            ];

            $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $this->apiKey,
                    'content-type' => 'application/json'
                ],
                'json' => $data,
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 201) {
                $this->logger->info('Email Brevo envoye a ' . $clientEmail);
                return true;
            } else {
                $this->logger->error('Erreur Brevo: HTTP ' . $statusCode);
                return false;
            }

        } catch (\Exception $e) {
            $this->logger->error('Exception Brevo: ' . $e->getMessage());
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
            $htmlContent = $this->getAdminNotificationHtml(
                $clientName, $clientEmail, $clientPhone, $programmeNom, $nbrePersonnes, $prixTotal
            );

            $data = [
                'sender' => [
                    'name' => $this->senderName,
                    'email' => $this->senderEmail
                ],
                'to' => [
                    [
                        'email' => $this->senderEmail,
                        'name' => 'Admin TunisiaJourney'
                    ]
                ],
                'subject' => 'Nouvelle reservation - TunisiaJourney',
                'htmlContent' => $htmlContent
            ];

            $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $this->apiKey,
                    'content-type' => 'application/json'
                ],
                'json' => $data,
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 201) {
                $this->logger->info('Notification admin Brevo envoyee');
                return true;
            }
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Exception admin Brevo: ' . $e->getMessage());
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
        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Confirmation TunisiaJourney</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: "Georgia", "Times New Roman", Times, serif;
                    background-color: #f5f0e8;
                    padding: 40px 20px;
                    line-height: 1.6;
                }
                
                .email-container {
                    max-width: 580px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                }
                
                .header {
                    background: #1a2a3a;
                    padding: 35px 30px;
                    text-align: center;
                    border-bottom: 3px solid #c9a03d;
                }
                
                .logo h1 {
                    font-family: "Georgia", "Times New Roman", Times, serif;
                    font-size: 28px;
                    font-weight: normal;
                    color: #ffffff;
                    letter-spacing: 2px;
                    margin: 0;
                }
                
                .logo span {
                    color: #c9a03d;
                    font-weight: bold;
                }
                
                .logo p {
                    color: rgba(255,255,255,0.6);
                    font-size: 10px;
                    letter-spacing: 3px;
                    margin-top: 8px;
                    text-transform: uppercase;
                }
                
                .content {
                    padding: 40px 35px;
                }
                
                .greeting {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .greeting h2 {
                    font-family: "Georgia", "Times New Roman", Times, serif;
                    font-size: 24px;
                    font-weight: normal;
                    color: #1a2a3a;
                    margin-bottom: 8px;
                }
                
                .greeting p {
                    color: #6b7c8e;
                    font-size: 14px;
                }
                
                .divider {
                    width: 50px;
                    height: 1px;
                    background: #c9a03d;
                    margin: 20px auto;
                }
                
                .welcome-message {
                    background: #f8f6f2;
                    border-left: 3px solid #c9a03d;
                    padding: 18px 22px;
                    margin: 25px 0;
                }
                
                .welcome-message p {
                    color: #4a5b6e;
                    font-size: 14px;
                    line-height: 1.7;
                    margin: 0;
                }
                
                .welcome-message strong {
                    color: #1a2a3a;
                    font-size: 15px;
                }
                
                .details-card {
                    background: #faf8f5;
                    padding: 25px;
                    margin: 25px 0;
                    border: 1px solid #e8e0d5;
                }
                
                .details-title {
                    font-family: "Georgia", "Times New Roman", Times, serif;
                    font-size: 15px;
                    font-weight: bold;
                    color: #1a2a3a;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #e8e0d5;
                    letter-spacing: 1px;
                }
                
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px 0;
                    border-bottom: 1px solid #e8e0d5;
                }
                
                .detail-row:last-child {
                    border-bottom: none;
                }
                
                .detail-label {
                    color: #6b7c8e;
                    font-size: 13px;
                }
                
                .detail-value {
                    color: #1a2a3a;
                    font-weight: 600;
                    font-size: 14px;
                }
                
                .price-box {
                    background: #1a2a3a;
                    padding: 18px 25px;
                    margin-top: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .price-label {
                    color: rgba(255,255,255,0.7);
                    font-size: 13px;
                    letter-spacing: 1px;
                }
                
                .price-amount {
                    color: #c9a03d;
                    font-size: 26px;
                    font-weight: bold;
                    font-family: "Georgia", "Times New Roman", Times, serif;
                }
                
                .price-amount small {
                    font-size: 12px;
                    font-weight: normal;
                }
                
                .text-center {
                    text-align: center;
                }
                
                .thanks-section {
                    text-align: center;
                    margin: 30px 0 20px;
                    padding: 25px 20px;
                    background: #f8f6f2;
                }
                
                .thanks-section h3 {
                    font-family: "Georgia", "Times New Roman", Times, serif;
                    font-size: 18px;
                    font-weight: normal;
                    color: #1a2a3a;
                    margin-bottom: 10px;
                }
                
                .thanks-section p {
                    color: #6b7c8e;
                    font-size: 13px;
                    line-height: 1.7;
                }
                
                .footer {
                    background: #f8f6f2;
                    padding: 25px 35px;
                    text-align: center;
                    border-top: 1px solid #e8e0d5;
                }
                
                .footer p {
                    color: #9aaebf;
                    font-size: 11px;
                    margin: 5px 0;
                }
                
                .footer a {
                    color: #c9a03d;
                    text-decoration: none;
                }
                
                @media (max-width: 600px) {
                    .content { padding: 25px 20px; }
                    .detail-row { flex-direction: column; align-items: flex-start; gap: 5px; }
                    .price-box { flex-direction: column; gap: 10px; text-align: center; }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div class="logo">
                        <h1>Tunisia<span>Journey</span></h1>
                        <p>EXPLOREZ LA TUNISIE</p>
                    </div>
                </div>
                
                <div class="content">
                    <div class="greeting">
                        <h2>Bonjour ' . htmlspecialchars($clientName) . '</h2>
                        <p>Nous sommes ravis de vous compter parmi nos voyageurs</p>
                        <div class="divider"></div>
                    </div>
                    
                    <div class="welcome-message">
                        <p><strong>Bienvenue chez TunisiaJourney</strong><br>
                        Merci d\'avoir choisi notre agence pour votre prochaine aventure. Nous mettons tout en oeuvre pour que votre experience soit inoubliable.</p>
                    </div>
                    
                    <div class="details-card">
                        <div class="details-title">
                            DETAILS DE VOTRE RESERVATION
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Programme</span>
                            <span class="detail-value">' . htmlspecialchars($programmeNom) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Lieu</span>
                            <span class="detail-value">' . htmlspecialchars($lieu) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date de depart</span>
                            <span class="detail-value">' . htmlspecialchars($programmeDate) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nombre de voyageurs</span>
                            <span class="detail-value">' . $nbrePersonnes . ' personne(s)</span>
                        </div>
                        
                        <div class="price-box">
                            <span class="price-label">MONTANT TOTAL</span>
                            <span class="price-amount">' . number_format($prixTotal, 2) . ' <small>DT</small></span>
                        </div>
                    </div>
                    
                    <div class="thanks-section">
                        <h3>Merci pour votre confiance</h3>
                        <p>Vous allez vivre une experience unique au coeur de la Tunisie.<br>
                        Notre equipe reste a votre disposition pour toute question.<br>
                        L\'aventure commence ici.</p>
                    </div>
                    
                    <div class="text-center">
                        <p style="font-size: 12px; color: #9aaebf; margin-top: 15px;">
                            Un email de confirmation vous a ete envoye.<br>
                            Vous serez redirige vers la page de paiement securise.
                        </p>
                    </div>
                </div>
                
                <div class="footer">
                    <p>TunisiaJourney - Agence de voyage</p>
                    <p>Tunis, Tunisie | contact@tunisiajourney.tn</p>
                    <p>(c) ' . date('Y') . ' TunisiaJourney - Tous droits reserves</p>
                    <p style="font-size: 10px;">Cet email a ete envoye a ' . htmlspecialchars($clientEmail) . '</p>
                </div>
            </div>
        </body>
        </html>';
    }

    private function getAdminNotificationHtml(
        string $clientName,
        string $clientEmail,
        string $clientPhone,
        string $programmeNom,
        int $nbrePersonnes,
        float $prixTotal
    ): string {
        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Nouvelle reservation - TunisiaJourney</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: "Georgia", "Times New Roman", Times, serif;
                    background: #f0ebe4;
                    padding: 40px 20px;
                }
                .container {
                    max-width: 550px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                }
                .header {
                    background: #1a2a3a;
                    padding: 25px 30px;
                    text-align: center;
                    border-bottom: 3px solid #c9a03d;
                }
                .header h1 {
                    color: white;
                    font-size: 22px;
                    font-weight: normal;
                    letter-spacing: 2px;
                }
                .header span { color: #c9a03d; font-weight: bold; }
                .badge {
                    background: #c9a03d;
                    color: #1a2a3a;
                    padding: 5px 14px;
                    display: inline-block;
                    font-size: 11px;
                    font-weight: bold;
                    letter-spacing: 1px;
                    margin-top: 12px;
                }
                .content { padding: 30px; }
                .alert-title {
                    font-size: 16px;
                    font-weight: bold;
                    color: #1a2a3a;
                    margin-bottom: 20px;
                    letter-spacing: 1px;
                }
                .info-card {
                    background: #faf8f5;
                    padding: 20px;
                    border: 1px solid #e8e0d5;
                    margin-bottom: 20px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #e8e0d5;
                }
                .info-row:last-child { border-bottom: none; }
                .info-label { color: #6b7c8e; font-size: 12px; letter-spacing: 0.5px; }
                .info-value { color: #1a2a3a; font-weight: 600; font-size: 13px; }
                .total-box {
                    background: #1a2a3a;
                    padding: 15px 20px;
                    display: flex;
                    justify-content: space-between;
                    color: #c9a03d;
                }
                .total-box span:first-child { font-size: 12px; letter-spacing: 1px; }
                .total-box span:last-child { font-size: 18px; font-weight: bold; }
                .footer {
                    background: #faf8f5;
                    padding: 20px;
                    text-align: center;
                    font-size: 10px;
                    color: #9aaebf;
                    border-top: 1px solid #e8e0d5;
                }
                @media (max-width: 500px) {
                    .info-row { flex-direction: column; gap: 5px; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Tunisia<span>Journey</span></h1>
                    <div class="badge">NOUVELLE RESERVATION</div>
                </div>
                <div class="content">
                    <div class="alert-title">
                        Une nouvelle aventure commence
                    </div>
                    <div class="info-card">
                        <div class="info-row">
                            <span class="info-label">Client</span>
                            <span class="info-value">' . htmlspecialchars($clientName) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value">' . htmlspecialchars($clientEmail) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Telephone</span>
                            <span class="info-value">' . htmlspecialchars($clientPhone) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Programme</span>
                            <span class="info-value">' . htmlspecialchars($programmeNom) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Personnes</span>
                            <span class="info-value">' . $nbrePersonnes . '</span>
                        </div>
                    </div>
                    <div class="total-box">
                        <span>MONTANT TOTAL</span>
                        <span>' . number_format($prixTotal, 2) . ' DT</span>
                    </div>
                    <p style="font-size: 11px; color: #6b7c8e; margin-top: 20px; text-align: center;">
                        Connectez-vous a l\'administration pour voir tous les details et gerer cette reservation.
                    </p>
                </div>
                <div class="footer">
                    <p>TunisiaJourney - Notification automatique</p>
                    <p>(c) ' . date('Y') . ' Tous droits reserves</p>
                </div>
            </div>
        </body>
        </html>';
    }
}