<?php

namespace App\Controller;

use App\Entity\ReservationProg;
use App\Entity\Programme;
use App\Entity\User;
use App\Service\EmailService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/reservation')]
class ReservationProgController extends AbstractController
{
    #[Route('/new/{idProg}', name: 'app_reservation_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        EmailService $emailService,
        StripeService $stripeService,
        ?string $idProg = null
    ): Response {

        // ✅ VÉRIFICATION : Seuls les MEMBRES (connectés) peuvent réserver
        $user = $this->getUser();
        
        // Si l'utilisateur n'est PAS connecté → redirection vers login
        if (!$user) {
            $this->addFlash('warning', '⚠️ Veuillez vous connecter ou créer un compte pour effectuer une réservation.');
            return $this->redirectToRoute('app_login');
        }
        
        // 🔥 Récupérer l'utilisateur complet depuis la base de données
        $userRepo = $entityManager->getRepository(User::class);
        $completeUser = null;
        
        if (method_exists($user, 'getId')) {
            $completeUser = $userRepo->find($user->getId());
        } else {
            $completeUser = $userRepo->findOneBy(['email' => $user->getUserIdentifier()]);
        }
        
        if (!$completeUser) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProg]);
        }
        
        // Vérifier le rôle de l'utilisateur (MEMBRE uniquement, pas ADMIN)
        $userRole = $completeUser->getRole() ?? '';
        
        // Si l'utilisateur est ADMIN → refuser la réservation
        if (strtoupper($userRole) === 'ADMIN') {
            $this->addFlash('error', '❌ Les administrateurs ne peuvent pas effectuer de réservation. Veuillez utiliser un compte membre.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProg]);
        }
        
        // Vérifier que le rôle est MEMBRE
        if (strtoupper($userRole) !== 'MEMBRE') {
            $this->addFlash('error', '❌ Seuls les membres peuvent effectuer des réservations.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProg]);
        }

        // Récupérer le programme
        $programme = $entityManager->getRepository(Programme::class)->find($idProg);

        if (!$programme) {
            $this->addFlash('error', 'Programme non trouvé');
            return $this->redirectToRoute('app_voyage_index');
        }

        // Récupérer les données du formulaire
        $nom      = trim($request->request->get('nom', ''));
        $prenom   = trim($request->request->get('prenom', ''));
        $telephone = trim($request->request->get('telephone', ''));
        $email    = trim($request->request->get('email', ''));
        $nbre     = $request->request->get('nbre', '');

        // ✅ UTILISER LES INFOS DE L'UTILISATEUR CONNECTÉ SI LES CHAMPS SONT VIDES
        if (empty($nom) && $completeUser->getNom()) {
            $nom = $completeUser->getNom();
        }
        if (empty($prenom) && $completeUser->getPrenom()) {
            $prenom = $completeUser->getPrenom();
        }
        if (empty($telephone) && $completeUser->getTelephone()) {
            $telephone = $completeUser->getTelephone();
        }
        if (empty($email) && $completeUser->getEmail()) {
            $email = $completeUser->getEmail();
        }

        // Créer la réservation
        $reservation = new ReservationProg();
        $reservation->setNom($nom);
        $reservation->setPrenom($prenom);
        $reservation->setTelephone($telephone);
        $reservation->setEmail($email);
        $reservation->setNbre(is_numeric($nbre) ? (int)$nbre : 0);
        $reservation->setIdP($programme->getIdProg());
        $reservation->setDateProgramme(new \DateTime());
        $reservation->setStatutPaiement('en_attente');
        
        // ✅ Lier l'utilisateur connecté (MEMBRE)
        $reservation->setUserId($completeUser->getId());

        // ✅ VALIDATION SYMFONY
        $errors = $validator->validate($reservation);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('app_programme_show', [
                'idProg' => $programme->getIdProg()
            ]);
        }

        // Calculer le prix total
        $prixTotal = $programme->getVoyage()->getPrix() * (int)$nbre;
        $reservation->setPrixProg((float)$prixTotal);

        // Sauvegarder
        try {
            $entityManager->persist($reservation);
            $entityManager->flush();
            
            // Envoi des emails (avec gestion d'erreur silencieuse)
            $clientFullName = $prenom . ' ' . $nom;
            $programmeDate = $programme->getDateDebut()->format('d/m/Y');
            $lieu = $programme->getLieu();
            
            try {
                $emailService->sendReservationConfirmation(
                    $email,
                    $clientFullName,
                    $programme->getNom(),
                    $programmeDate,
                    $lieu,
                    (int)$nbre,
                    $prixTotal
                );
                $emailService->sendAdminNotification(
                    $clientFullName,
                    $email,
                    $telephone,
                    $programme->getNom(),
                    (int)$nbre,
                    $prixTotal
                );
                $this->addFlash('success', '✅ Réservation créée ! Redirection vers la page de paiement...');
            } catch (\Exception $e) {
                $this->addFlash('success', '✅ Réservation créée ! Redirection vers la page de paiement...');
            }
            
            // ✅ REDIRECTION VERS STRIPE POUR LE PAIEMENT
            return $this->redirectToRoute('stripe_checkout', ['idReservation' => $reservation->getIdRP()]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_programme_show', [
            'idProg' => $programme->getIdProg()
        ]);
    }

    #[Route('/test-brevo', name: 'test_brevo')]
    public function testBrevo(\Symfony\Component\Mailer\MailerInterface $mailer): Response
    {
        try {
            $email = (new \Symfony\Component\Mime\Email())
                ->from('souhamzoughi01@gmail.com')
                ->to('souhamzoughi01@gmail.com')
                ->subject('Test Brevo')
                ->text('Ceci est un test de Brevo');
            
            $mailer->send($email);
            return new Response('✅ Email envoyé avec succès via Brevo !');
        } catch (\Exception $e) {
            return new Response('❌ Erreur Brevo: ' . $e->getMessage());
        }
    }
}