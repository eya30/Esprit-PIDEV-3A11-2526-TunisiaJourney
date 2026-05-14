<?php

namespace App\Controller;

use App\Entity\ReservationProg;
use App\Entity\Programme;
use App\Entity\User;
use App\Service\BrevoEmailService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
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
        BrevoEmailService $emailService,
        StripeService $stripeService,
        Connection $connection,
        ?string $idProg = null
    ): Response {

        // ✅ VÉRIFICATION : Seuls les MEMBRES (connectés) peuvent réserver
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', '⚠️ Veuillez vous connecter ou créer un compte pour effectuer une réservation.');
            return $this->redirectToRoute('app_login');
        }

        if (!$user instanceof User) {
            $this->addFlash('error', 'Type d\'utilisateur non reconnu.');
            return $this->redirectToRoute('app_login');
        }

        $userRepo     = $entityManager->getRepository(User::class);
        $completeUser = $userRepo->find($user->getId());

        if (!$completeUser) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProg]);
        }

        $userRole = $completeUser->getRole() ?? '';

        if (strtoupper($userRole) === 'ADMIN') {
            $this->addFlash('error', '❌ Les administrateurs ne peuvent pas effectuer de réservation.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProg]);
        }

        if (strtoupper($userRole) !== 'MEMBRE') {
            $this->addFlash('error', '❌ Seuls les membres peuvent effectuer des réservations.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProg]);
        }

        $programme = $entityManager->getRepository(Programme::class)->find($idProg);

        if (!$programme) {
            $this->addFlash('error', 'Programme non trouvé');
            return $this->redirectToRoute('app_voyage_index');
        }

        $nom       = trim((string)$request->request->get('nom', ''));
        $prenom    = trim((string)$request->request->get('prenom', ''));
        $telephone = trim((string)$request->request->get('telephone', ''));
        $email     = trim((string)$request->request->get('email', ''));
        $nbre      = $request->request->get('nbre', '');
        $codePromo = trim((string)$request->request->get('code_promo', ''));

        if ($nom === '' && $completeUser->getNom()) {
            $nom = $completeUser->getNom();
        }
        if ($prenom === '' && $completeUser->getPrenom()) {
            $prenom = $completeUser->getPrenom();
        }
        if ($telephone === '' && $completeUser->getTelephone()) {
            $telephone = $completeUser->getTelephone();
        }
        if ($email === '' && $completeUser->getEmail()) {
            $email = $completeUser->getEmail();
        }

        $idProgramme = $programme->getIdProg();
        if ($idProgramme === null) {
            $this->addFlash('error', 'Identifiant du programme invalide.');
            return $this->redirectToRoute('app_voyage_index');
        }

        $reservation = new ReservationProg();
        $reservation->setNom($nom);
        $reservation->setPrenom($prenom);
        $reservation->setTelephone($telephone);
        $reservation->setEmail($email);
        $reservation->setNbre(is_numeric($nbre) ? (int)$nbre : 0);
        $reservation->setIdP($idProgramme);
        $reservation->setDateProgramme(new \DateTime());
        $reservation->setStatutPaiement('en_attente');
        $reservation->setUserId($completeUser->getId());

        $errors = $validator->validate($reservation);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('app_programme_show', [
                'idProg' => $idProgramme,
            ]);
        }

        $voyage = $programme->getVoyage();
        if ($voyage === null) {
            $this->addFlash('error', 'Aucun voyage associé à ce programme.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProgramme]);
        }

        $prixTotal = $voyage->getPrix() * (int)$nbre;

        // ========== VALIDATION & APPLICATION DU CODE PROMO ==========
        $reductionPourcentage = 0;
        $codePromoApplique    = null;

        if ($codePromo !== '') {
            $today = (new \DateTime())->format('Y-m-d');
            $promo = $connection->fetchAssociative(
                "SELECT * FROM code_promo
                 WHERE code = ? AND statut = 'actif'
                   AND date_debut <= ? AND date_fin >= ?",
                [$codePromo, $today, $today]
            );

            if ($promo) {
                $reductionPourcentage = (float) $promo['pourcentage_reduction'];
                $codePromoApplique    = $codePromo;
                $prixTotal            = $prixTotal * (1 - $reductionPourcentage / 100);
                $this->addFlash('success', sprintf(
                    '🎉 Code promo "%s" appliqué ! Réduction de %d%%.',
                    $codePromoApplique,
                    (int)$reductionPourcentage
                ));
            } else {
                $this->addFlash('error', '❌ Code promo invalide, expiré ou inactif.');
                return $this->redirectToRoute('app_programme_show', ['idProg' => $idProgramme]);
            }
        }
        // =============================================================

        $reservation->setPrixProg((float)$prixTotal);

        $dateDebut = $programme->getDateDebut();
        if ($dateDebut === null) {
            $this->addFlash('error', 'Date de début du programme non définie.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProgramme]);
        }

        $programmeNom   = (string)($programme->getNom() ?? '');
        $lieu           = (string)($programme->getLieu() ?? '');
        $programmeDate  = $dateDebut->format('d/m/Y');
        $clientFullName = $prenom . ' ' . $nom;

        try {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $emailSent = $emailService->sendReservationConfirmation(
                $email,
                $clientFullName,
                $programmeNom,
                $programmeDate,
                $lieu,
                (int)$nbre,
                $prixTotal
            );

            $emailService->sendAdminNotification(
                $clientFullName,
                $email,
                $telephone,
                $programmeNom,
                (int)$nbre,
                $prixTotal
            );

            if ($emailSent) {
                $this->addFlash('success', '✅ Réservation créée ! Email envoyé.');
            } else {
                $this->addFlash('success', '✅ Réservation créée !');
            }

            return $this->redirectToRoute('stripe_checkout', ['idReservation' => $reservation->getIdRP()]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_programme_show', [
            'idProg' => $idProgramme,
        ]);
    }

    // ========== AJAX : VÉRIFIER UN CODE PROMO ==========
    #[Route('/api/verifier-code-promo', name: 'api_verifier_code_promo', methods: ['POST'])]
    public function verifierCodePromo(Request $request, Connection $connection): Response
    {
        $data      = json_decode($request->getContent(), true);
        $code      = trim((string)($data['code'] ?? ''));
        $prixBase  = (float)($data['prix_base'] ?? 0);

        if ($code === '') {
            return $this->json(['valid' => false, 'message' => 'Code vide.']);
        }

        $today = (new \DateTime())->format('Y-m-d');
        $promo = $connection->fetchAssociative(
            "SELECT * FROM code_promo
             WHERE code = ? AND statut = 'actif'
               AND date_debut <= ? AND date_fin >= ?",
            [$code, $today, $today]
        );

        if (!$promo) {
            return $this->json(['valid' => false, 'message' => 'Code invalide, expiré ou inactif.']);
        }

        $reduction   = (float) $promo['pourcentage_reduction'];
        $prixReduit  = $prixBase * (1 - $reduction / 100);

        return $this->json([
            'valid'       => true,
            'reduction'   => $reduction,
            'prix_reduit' => round($prixReduit, 2),
            'message'     => sprintf('🎉 Code valide ! Réduction de %d%% appliquée.', (int)$reduction),
        ]);
    }

    #[Route('/test-brevo', name: 'test_brevo')]
    public function testBrevo(BrevoEmailService $emailService): Response
    {
        $result = $emailService->sendReservationConfirmation(
            'souhamzoughi01@gmail.com',
            'Test User',
            'Programme Test',
            '25/12/2024',
            'Tunis',
            2,
            500.00
        );

        if ($result) {
            return new Response('✅ Email envoyé avec succès via API Brevo ! Vérifiez votre boîte mail.');
        } else {
            return new Response('❌ Erreur lors de l\'envoi. Vérifiez les logs.');
        }
    }
}