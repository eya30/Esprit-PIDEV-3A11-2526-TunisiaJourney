<?php

namespace App\Controller;

use App\Entity\ReservationProg;
use App\Entity\Programme;
use App\Entity\User;
use App\Service\BrevoEmailService;
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
        BrevoEmailService $emailService,
        StripeService $stripeService,
        ?string $idProg = null
    ): Response {

        // ✅ VÉRIFICATION : Seuls les MEMBRES (connectés) peuvent réserver
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', '⚠️ Veuillez vous connecter ou créer un compte pour effectuer une réservation.');
            return $this->redirectToRoute('app_login');
        }

        // FIX P1013 — getUser() retourne UserInterface qui ne déclare pas getId().
        // Le instanceof garantit à Intelephense et PHPStan le type concret User.
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

        // FIX :71–:74 — request->get() retourne mixed ; on force string avant trim()
        $nom       = trim((string)$request->request->get('nom', ''));
        $prenom    = trim((string)$request->request->get('prenom', ''));
        $telephone = trim((string)$request->request->get('telephone', ''));
        $email     = trim((string)$request->request->get('email', ''));
        $nbre      = $request->request->get('nbre', '');

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

        // FIX :96 — getIdProg() peut retourner null ; on garantit une string
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

        // FIX :112 — getVoyage() peut retourner null
        $voyage = $programme->getVoyage();
        if ($voyage === null) {
            $this->addFlash('error', 'Aucun voyage associé à ce programme.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProgramme]);
        }

        $prixTotal = $voyage->getPrix() * (int)$nbre;
        $reservation->setPrixProg((float)$prixTotal);

        // FIX :120 — getDateDebut() peut retourner null
        $dateDebut = $programme->getDateDebut();
        if ($dateDebut === null) {
            $this->addFlash('error', 'Date de début du programme non définie.');
            return $this->redirectToRoute('app_programme_show', ['idProg' => $idProgramme]);
        }

        // FIX :127/:129/:138 — getNom()/getLieu() peuvent retourner null ; on cast en string
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