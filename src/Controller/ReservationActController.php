<?php

namespace App\Controller;

use App\Entity\ListeAttente;
use App\Repository\CodePromoRepository;
use App\Repository\ListeAttenteRepository;
use App\Repository\ReservationActRepository;
use App\Service\SmsActService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\ReservationAct;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;

#[Route('/reservation-act')]
class ReservationActController extends AbstractController
{
    #[Route('/billet/{IDRes}', name: 'app_reservationact_billet', methods: ['GET'])]
    public function billet(
        Connection $connection,
        int $IDRes
    ): Response {
        $reservation = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = ?",
            [$IDRes]
        );

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        if ($reservation['status'] === 'annulé') {
            $this->addFlash('error', 'Cette réservation a été annulée et n\'est plus valable.');
            return $this->redirectToRoute('app_home');
        }

        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$reservation['IDAct']]
        );

        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        // Correction ligne 54: S'assurer que le project_dir est une string
        $projectDir = $this->getParameter('kernel.project_dir');
        $projectDir = is_string($projectDir) ? $projectDir : '';
        $imagePath = $projectDir . '/public/images/billet.jpg';
        
        $imageContent = file_exists($imagePath) ? file_get_contents($imagePath) : false;
        $imageBase64 = ($imageContent !== false) ? 'data:image/jpeg;base64,' . base64_encode($imageContent) : '';

        return $this->render('activite/billet.html.twig', [
            'reservation' => $reservation,
            'activite'    => $activite,
            'imageBase64' => $imageBase64,
        ]);
    }

    /**
     * @param int $idActivite
     * @param EntityManagerInterface $em
     * @param ListeAttenteRepository $repo
     * @param SmsActService $smsActService
     * @return void
     */
    private function notifierListeAttente(
        int                    $idActivite,
        EntityManagerInterface $em,
        ListeAttenteRepository $repo,
        SmsActService          $smsActService
    ): void {
        error_log("=== NOTIFIER LISTE ATTENTE ===");
        error_log("📌 Activité ID: " . $idActivite);
        
        $prochain = $repo->trouverProchainEnAttente($idActivite);

        if (!$prochain) {
            error_log("❌ Aucune personne en liste d'attente");
            return;
        }
        
        error_log("✅ Personne trouvée: " . $prochain->getEmailUtilisateur());
        error_log("📞 Téléphone: " . $prochain->getTelephoneUtilisateur());

        $prochain->setStatut(ListeAttente::STATUT_NOTIFIE);
        $prochain->setDateNotification(new \DateTime());
        $prochain->setDateLimiteConfirmation((new \DateTime())->modify('+2 hours'));
        $em->flush();

        $telephone = $prochain->getTelephoneUtilisateur();
        error_log("📞 Téléphone après récupération: " . $telephone);

        if (empty($telephone)) {
            error_log("❌ Numéro de téléphone vide");
            return;
        }

        $token     = $prochain->getTokenConfirmation();
        $url       = $this->generateUrl('liste_attente_confirmer_page', ['token' => $token], 0);
        $ngrokBase = rtrim($_ENV['NGROK_URL'] ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), " /");
        $fullUrl   = $ngrokBase . $url;
        
        error_log("🔗 URL confirmation: " . $fullUrl);

        $message = $smsActService->generatePlaceMessage($fullUrl);
        error_log("📝 Message SMS: " . $message);
        
        $result = $smsActService->sendSms($telephone, $message);
        error_log("📊 Résultat envoi SMS: " . ($result ? "SUCCÈS" : "ÉCHEC"));
    }

    #[Route('/{IDRes}/cancel', name: 'app_reservationact_cancel', methods: ['POST'])]
    public function cancelReservation(
        int                      $IDRes,
        ReservationActRepository $repo,
        Connection               $connection,
        EntityManagerInterface   $em,
        ListeAttenteRepository   $listeRepo,
        SmsActService            $smsActService
    ): JsonResponse {
        $reservation = $repo->find($IDRes);

        if (!$reservation) {
            return new JsonResponse(['success' => false, 'message' => 'Réservation non trouvée'], 404);
        }

        if ($reservation->isCancelled()) {
            return new JsonResponse(['success' => false, 'message' => 'Cette réservation est déjà annulée'], 400);
        }

        $idActivite = $reservation->getIDAct();
        
        if ($idActivite === null) {
            return new JsonResponse(['success' => false, 'message' => 'ID activité invalide'], 400);
        }
        
        $repo->cancelReservation($reservation);
        $this->notifierListeAttente($idActivite, $em, $listeRepo, $smsActService);

        return new JsonResponse(['success' => true, 'message' => 'Réservation annulée avec succès']);
    }

    private function envoyerMailConfirmation(
        string $nom,
        string $prenom,
        string $email,
        string $titreActivite,
        int    $nombrePlaces,
        float  $prixTotal,
        int    $IDRes
    ): void {
        $apiKey = $_ENV['BREVO_API_KEY'] ?? '';
        if (!$apiKey) return;

        try {
            $ngrokUrl  = rtrim($_ENV['NGROK_URL'] ?? 'http://localhost', '/');
            $billetUrl = $ngrokUrl . '/reservation-act/billet/' . $IDRes;

            $htmlContent = $this->renderView('emails/reservation_confirmation.html.twig', [
                'nom'           => $nom,
                'prenom'        => $prenom,
                'titreActivite' => $titreActivite,
                'nombrePlaces'  => $nombrePlaces,
                'prixTotal'     => $prixTotal,
                'billetUrl'     => $billetUrl,
            ]);

            $brevo     = new \Brevo\Brevo($apiKey);
            $sender    = new SendTransacEmailRequestSender([
                'name'  => 'TunisiaJourney',
                'email' => 'chaimabejaoui79@gmail.com',
            ]);
            $recipient = new SendTransacEmailRequestToItem([
                'email' => $email,
                'name'  => $prenom . ' ' . $nom,
            ]);
            $emailRequest = new SendTransacEmailRequest([
                'subject'     => '🎟️ Votre billet — ' . $titreActivite,
                'sender'      => $sender,
                'to'          => [$recipient],
                'htmlContent' => $htmlContent,
            ]);

            $brevo->transactionalEmails->sendTransacEmail($emailRequest);

        } catch (\Exception $e) {
            // Ne bloque pas la réservation si le mail échoue
        }
    }

    #[Route('/verify-promo', name: 'app_reservationact_verify_promo', methods: ['GET'])]
    public function verifyPromo(
        Request             $request,
        CodePromoRepository $codePromoRepository
    ): JsonResponse {
        $codeRaw = $request->query->get('code', '');
        $code = is_string($codeRaw) ? trim($codeRaw) : '';

        if (!$code) {
            return new JsonResponse(['valide' => false, 'message' => 'Aucun code saisi.']);
        }

        $promo = $codePromoRepository->findOneBy(['code' => strtoupper($code)]);

        if (!$promo) {
            return new JsonResponse(['valide' => false, 'message' => 'Code promo inexistant.']);
        }

        if (!$promo->isValide()) {
            return new JsonResponse(['valide' => false, 'message' => 'Code promo expiré.']);
        }

        return new JsonResponse([
            'valide'      => true,
            'pourcentage' => $promo->getPourcentageReduction(),
            'message'     => 'Code valide — ' . $promo->getPourcentageReduction() . '% de réduction !',
        ]);
    }

    #[Route('/new/{IDAct}', name: 'app_reservationact_new', methods: ['GET', 'POST'])]
    public function new(
        Request                  $request,
        Connection               $connection,
        ValidatorInterface       $validator,
        CodePromoRepository      $codePromoRepository,
        ReservationActRepository $reservationRepo,
        int                      $IDAct
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user      = $this->getUser();
        $userId    = $user instanceof \App\Entity\User ? $user->getId() : null;
        $activite  = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$IDAct]
        );

        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        $placesReservees   = $reservationRepo->getTotalConfirmedPlacesByActiviteId($IDAct);
        $placesDisponibles = (int) $activite['CapaciteM'] - $placesReservees;

        $errors = [];
        $old    = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'nombrePlaces' => ''];
        if ($user instanceof \App\Entity\User) {
            $old['nom']    = $user->getNom() ?? '';
            $old['prenom'] = $user->getPrenom() ?? '';
            $old['email']  = $user->getEmail() ?? '';
        }

        if ($request->isMethod('POST')) {

            $nom        = trim((string) $request->request->get('nom', ''));
            $prenom     = trim((string) $request->request->get('prenom', ''));
            $email      = trim((string) $request->request->get('email', ''));
            $telephone  = trim((string) $request->request->get('telephone', ''));
            $rawPlaces  = trim((string) $request->request->get('nombrePlaces', ''));
            $codePromo  = trim(strtoupper((string) $request->request->get('codePromo', '')));
            $promoReduc = (int) $request->request->get('promoReduction', 0);

            $old = compact('nom', 'prenom', 'email', 'telephone') + ['nombrePlaces' => $rawPlaces];

            $reservation = new ReservationAct();
            $reservation->setNom($nom);
            $reservation->setPrenom($prenom);
            $reservation->setEmail($email);
            $reservation->setTelephone($telephone);
            $reservation->setStatus(ReservationAct::STATUS_CONFIRMED);

            if ($rawPlaces !== '' && ctype_digit($rawPlaces)) {
                $reservation->setNombrePlaces((int) $rawPlaces);
            }

            $violations = $validator->validate($reservation);

            foreach ($violations as $violation) {
                $field = lcfirst($violation->getPropertyPath());
                if (!isset($errors[$field])) {
                    $errors[$field] = $violation->getMessage();
                }
            }

            if (
                !isset($errors['nombrePlaces'])
                && $rawPlaces !== ''
                && ctype_digit($rawPlaces)
                && (int) $rawPlaces > $placesDisponibles
            ) {
                $errors['nombrePlaces'] = 'Seulement ' . $placesDisponibles . ' place(s) disponible(s).';
            }

            $reductionValidee = 0;
            if ($codePromo) {
                $promo = $codePromoRepository->findOneBy(['code' => $codePromo]);
                if ($promo && $promo->isValide()) {
                    $reductionValidee = $promo->getPourcentageReduction();
                }
            }

            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                if (empty($errors)) {
                    $nombrePlaces = (int) $rawPlaces;
                    $prixBase     = (float) $activite['Prix'] * $nombrePlaces;
                    $prixTotal    = $prixBase * (1 - $reductionValidee / 100);

                    $connection->executeStatement(
                        "INSERT INTO ReservationAct (id, IDAct, Nom, Prenom, email, telephone, DateReservation, NombrePlaces, Prix, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$userId, $IDAct, $nom, $prenom, $email, $telephone, date('Y-m-d'), $nombrePlaces, round($prixTotal, 2), ReservationAct::STATUS_CONFIRMED]
                    );

                    $IDRes = (int) $connection->lastInsertId();

                    $this->envoyerMailConfirmation(
                        $nom, $prenom, $email,
                        $activite['Titre'],
                        $nombrePlaces,
                        round($prixTotal, 2),
                        $IDRes
                    );

                    return new JsonResponse(['success' => true]);
                }

                return new JsonResponse(['success' => false, 'errors' => $errors]);
            }

            if (empty($errors)) {
                $nombrePlaces = (int) $rawPlaces;
                $prixBase     = (float) $activite['Prix'] * $nombrePlaces;
                $prixTotal    = $prixBase * (1 - $reductionValidee / 100);

                $connection->executeStatement(
                    "INSERT INTO ReservationAct (id, IDAct, Nom, Prenom, email, telephone, DateReservation, NombrePlaces, Prix, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$userId, $IDAct, $nom, $prenom, $email, $telephone, date('Y-m-d'), $nombrePlaces, round($prixTotal, 2), ReservationAct::STATUS_CONFIRMED]
                );

                $IDRes = (int) $connection->lastInsertId();

                $this->envoyerMailConfirmation(
                    $nom, $prenom, $email,
                    $activite['Titre'],
                    $nombrePlaces,
                    round($prixTotal, 2),
                    $IDRes
                );

                $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');
                return $this->redirectToRoute('app_activite_show', ['IDAct' => $IDAct]);
            }
        }

        $apiKey = $_ENV['EXCHANGERATE_API_KEY'] ?? '';

        return $this->render('activite/reservationact.html.twig', [
            'activite'           => $activite,
            'errors'             => $errors,
            'old'                => $old,
            'placesDisponibles'  => $placesDisponibles,
            'exchangeRateApiKey' => $apiKey,
        ]);
    }

    #[Route('/test-sms', name: 'test_sms', methods: ['GET'])]
    public function testSms(SmsActService $smsActService): JsonResponse
    {
        $numero = '+21698830328';
        $message = '🧪 Test SMS depuis TunisiaJourney - ' . date('H:i:s');
        
        error_log("=== TEST MANUEL SMS ===");
        error_log("📞 Numéro: " . $numero);
        error_log("📝 Message: " . $message);
        
        $resultat = $smsActService->sendSms($numero, $message);
        
        return new JsonResponse([
            'success' => $resultat,
            'numero' => $numero,
            'message' => $message,
            'resultat' => $resultat ? 'SMS envoyé avec succès' : 'Échec de l\'envoi',
            'heure' => date('H:i:s')
        ]);
    }

    #[Route('/check-twilio', name: 'check_twilio', methods: ['GET'])]
    public function checkTwilio(): JsonResponse
    {
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID_CHAIMA'] ?? '';
        $authToken = $_ENV['TWILIO_AUTH_TOKEN_CHAIMA'] ?? '';
        $phoneNumber = $_ENV['TWILIO_PHONE_NUMBER_CHAIMA'] ?? '';
        
        return new JsonResponse([
            'account_sid' => $accountSid ? substr($accountSid, 0, 6) . '...' : 'NON',
            'auth_token' => $authToken ? '✅ Défini' : '❌ NON',
            'phone_number' => $phoneNumber ?: '❌ NON',
        ]);
    }
}