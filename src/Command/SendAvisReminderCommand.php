<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendAvisReminderCommand extends Command
{
    protected static $defaultName = 'app:send-avis-reminder';
    protected static $defaultDescription = 'Envoie un email aux clients pour leur demander un avis 24h après leur séjour';

    public function __construct(
        private Connection $connection,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('📧 Recherche des réservations terminées...');

        // Requête modifiée : on enlève la condition sur token_avis
        $reservations = $this->connection->fetchAllAssociative(
            "SELECT r.*, u.email, u.prenom, u.nom, c.idCh, c.type as chambre_type, h.nom as hotel_nom
             FROM reservation_chambre r
             JOIN utilisateur u ON r.idUtilisateur = u.id
             JOIN chambre c ON r.idCh = c.idCh
             JOIN hotel h ON c.idH = h.idH
             WHERE r.dateFin <= DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );

        $output->writeln("📊 " . count($reservations) . " réservation(s) trouvée(s)");

        $compteur = 0;

        foreach ($reservations as $reservation) {
            // Vérifier si l'email est valide
            if (!filter_var($reservation['email'], FILTER_VALIDATE_EMAIL)) {
                $output->writeln("⚠️ Email invalide pour ID réservation " . $reservation['idRes'] . " : " . $reservation['email']);
                continue;
            }

            $token = bin2hex(random_bytes(32));
            
            $this->connection->update('reservation_chambre', 
                ['token_avis' => $token],
                ['idRes' => $reservation['idRes']]
            );
            
            $url = "http://127.0.0.1:8000/avis/chambre/" . $token;
            
            $html = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .button { background: #E31B23; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
                </style>
            </head>
            <body>
                <h2>Bonjour {$reservation['prenom']} {$reservation['nom']},</h2>
                <p>Nous espérons que votre séjour à l'hôtel <strong>{$reservation['hotel_nom']}</strong> s'est bien passé.</p>
                <p>Votre avis nous intéresse !</p>
                <p><a href='{$url}' class='button'>⭐ Donner mon avis ⭐</a></p>
                <p><small>Ce lien est valable 30 jours. Si vous ne souhaitez pas donner d'avis, ignorez cet email.</small></p>
                <hr>
                <p><small>TunisiaJourney</small></p>
            </body>
            </html>";
            
            $email = (new Email())
                ->from('noreply@tunisiajourney.tn')
                ->to($reservation['email'])
                ->subject('Donnez votre avis sur votre séjour à ' . $reservation['hotel_nom'])
                ->html($html);
            
            try {
                $this->mailer->send($email);
                $compteur++;
                $output->writeln("✅ Email envoyé à : " . $reservation['email']);
            } catch (\Exception $e) {
                $output->writeln("❌ Erreur pour " . $reservation['email'] . " : " . $e->getMessage());
            }
        }

        $output->writeln("✅ {$compteur} email(s) envoyé(s) avec succès !");
        return Command::SUCCESS;
    }
}