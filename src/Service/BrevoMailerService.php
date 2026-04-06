<?php

namespace App\Service;

use GuzzleHttp\Client;

class BrevoMailerService
{
    private string $apiKey;
    private Client $client;
    private string $fromEmail = 'souhamzoughi01@gmail.com';
    private string $fromName = 'TunisiaJourney';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.brevo.com/v3/',
            'headers' => [
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    public function sendConfirmationEmail(string $to, string $nom, string $prenom, array $reservationData): bool
    {
        try {
            $prixTotal = number_format($reservationData['prix_total'], 2, ',', ' ');
            $dateDebut = date('d/m/Y', strtotime($reservationData['date_debut']));
            $dateFin = date('d/m/Y', strtotime($reservationData['date_fin']));

            $htmlContent = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Confirmation réservation - TunisiaJourney</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body {
                        font-family: "Georgia", "Times New Roman", serif;
                        background-color: #F5F5F0;
                        margin: 0;
                        padding: 30px 20px;
                    }
                    .container {
                        max-width: 580px;
                        margin: 0 auto;
                        background: #FFFFFF;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
                        border: 1px solid #EAEAE4;
                    }
                    .header {
                        background-color: #FFFFFF;
                        padding: 35px 30px 20px 30px;
                        text-align: center;
                        border-bottom: 2px solid #C0392B;
                    }
                    .header h1 {
                        color: #C0392B;
                        font-size: 28px;
                        margin: 0;
                        font-weight: 600;
                    }
                    .header p {
                        color: #7F8C8D;
                        margin: 10px 0 0;
                        font-size: 13px;
                        font-style: italic;
                    }
                    .content { padding: 35px 30px; }
                    .greeting {
                        font-size: 18px;
                        color: #2C3E50;
                        margin-bottom: 20px;
                    }
                    .greeting strong { color: #C0392B; }
                    .message {
                        color: #2C3E50;
                        line-height: 1.7;
                        margin-bottom: 30px;
                        font-size: 14px;
                    }
                    .highlight { color: #C0392B; font-weight: 600; }
                    .details {
                        background: #F9F9F7;
                        padding: 25px;
                        border-radius: 6px;
                        margin: 25px 0;
                        border: 1px solid #EAEAE4;
                    }
                    .detail-title {
                        font-size: 16px;
                        font-weight: 600;
                        color: #2C3E50;
                        margin-bottom: 20px;
                        border-left: 3px solid #C0392B;
                        padding-left: 12px;
                    }
                    .detail-row {
                        display: flex;
                        justify-content: space-between;
                        padding: 12px 0;
                        border-bottom: 1px solid #EAEAE4;
                    }
                    .detail-row:last-child { border-bottom: none; }
                    .detail-label { color: #7F8C8D; font-size: 13px; }
                    .detail-value { color: #2C3E50; font-weight: 500; font-size: 14px; }
                    .price { font-size: 18px; font-weight: 700; color: #C0392B; }
                    .thanks {
                        text-align: center;
                        margin: 30px 0 20px;
                        padding: 20px 0;
                        border-top: 1px solid #EAEAE4;
                        border-bottom: 1px solid #EAEAE4;
                    }
                    .thanks h3 { color: #2C3E50; margin-bottom: 10px; font-size: 16px; }
                    .thanks p { color: #7F8C8D; font-size: 13px; }
                    .reminder {
                        background: #F9F9F7;
                        padding: 18px 20px;
                        border-radius: 6px;
                        margin: 25px 0;
                        font-size: 12px;
                        color: #7F8C8D;
                        text-align: center;
                        border: 1px solid #EAEAE4;
                    }
                    .reminder strong { color: #2C3E50; }
                    .btn {
                        display: inline-block;
                        background: #C0392B;
                        color: white;
                        padding: 12px 32px;
                        text-decoration: none;
                        font-size: 13px;
                        font-weight: 500;
                        border-radius: 3px;
                    }
                    .btn:hover { background: #A93226; }
                    .footer {
                        background: #F9F9F7;
                        padding: 25px;
                        text-align: center;
                        font-size: 11px;
                        color: #BDC3C7;
                        border-top: 1px solid #EAEAE4;
                    }
                    .signature { margin-top: 15px; font-style: italic; color: #95A5A6; }
                    @media (max-width: 600px) {
                        .detail-row { flex-direction: column; }
                        .detail-value { margin-top: 5px; }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>TunisiaJourney</h1>
                        <p>L\'art du voyage authentique</p>
                    </div>
                    <div class="content">
                        <div class="greeting">
                            Cher/Chère <strong>' . htmlspecialchars($prenom) . ' ' . htmlspecialchars($nom) . '</strong>,
                        </div>
                        <div class="message">
                            Nous avons le plaisir de vous confirmer que votre réservation a été <span class="highlight">validée avec succès</span>.<br><br>
                            Toute l\'équipe <strong>TunisiaJourney</strong> vous remercie pour votre confiance.
                        </div>
                        <div class="details">
                            <div class="detail-title">DÉTAIL DE LA PRESTATION</div>
                            <div class="detail-row">
                                <span class="detail-label">Programme</span>
                                <span class="detail-value">' . htmlspecialchars($reservationData['programme_nom']) . '</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Destination</span>
                                <span class="detail-value">' . htmlspecialchars($reservationData['lieu']) . '</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date d\'arrivée</span>
                                <span class="detail-value">' . $dateDebut . '</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date de départ</span>
                                <span class="detail-value">' . $dateFin . '</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Voyageurs</span>
                                <span class="detail-value">' . $reservationData['nbre'] . ' personne(s)</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Montant total</span>
                                <span class="detail-value price">' . $prixTotal . ' TND</span>
                            </div>
                        </div>
                        <div class="thanks">
                            <h3>Merci d\'avoir choisi TunisiaJourney</h3>
                            <p>Nous mettons un point d\'honneur à vous offrir des voyages d\'exception.</p>
                        </div>
                        <div class="reminder">
                            <strong>Informations pratiques</strong><br><br>
                            • Une confirmation vous parviendra 48h avant votre départ<br>
                            • Pièce d\'identité en cours de validité exigée<br>
                            • Service client : <strong>+216 51 830 409</strong>
                        </div>
                        <div style="text-align: center;">
                            <a href="http://localhost:8000" class="btn">ACCÉDER À MON ESPACE</a>
                        </div>
                    </div>
                    <div class="footer">
                        <p>TunisiaJourney - Voyages et découvertes</p>
                        <p>Cet email a été envoyé à ' . htmlspecialchars($reservationData['email']) . '</p>
                        <p class="signature">À très bientôt sur les routes de Tunisie.</p>
                    </div>
                </div>
            </body>
            </html>';

            $data = [
                'sender' => [
                    'name' => $this->fromName,
                    'email' => $this->fromEmail,
                ],
                'to' => [
                    [
                        'email' => $to,
                        'name' => $prenom . ' ' . $nom,
                    ],
                ],
                'subject' => 'Confirmation de votre réservation - TunisiaJourney',
                'htmlContent' => $htmlContent,
            ];

            $response = $this->client->post('smtp/email', ['json' => $data]);
            $statusCode = $response->getStatusCode();

            return $statusCode === 201 || $statusCode === 200;
        } catch (\Throwable $e) {
            error_log('Brevo API Error: ' . $e->getMessage());
            return false;
        }
    }
}
