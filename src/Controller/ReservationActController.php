<?php

namespace App\Controller;

use App\Repository\CodePromoRepository;
use Doctrine\DBAL\Connection;
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
    // ── Page billet (accessible via QR code) ─────────────────────────────────
    #[Route('/billet/{IDRes}', name: 'app_reservationact_billet', methods: ['GET'])]
    public function billet(
        Connection $connection,
        int        $IDRes
    ): Response {
        $reservation = $connection->fetchAssociative(
            "SELECT * FROM ReservationAct WHERE IDRes = ?",
            [$IDRes]
        );

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$reservation['IDAct']]
        );

        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        // ── Conversion image en base64 pour affichage garanti sur iOS/Safari ──
        $imagePath   = $this->getParameter('kernel.project_dir') . '/public/images/billet.jpg';
        $imageBase64 = file_exists($imagePath)
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imagePath))
            : '';

        return $this->render('activite/billet.html.twig', [
            'reservation' => $reservation,
            'activite'    => $activite,
            'imageBase64' => $imageBase64,
        ]);
    }

    // ── Envoi du mail de confirmation via Brevo v4 ───────────────────────────
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
            // ── URL du billet via ngrok (accessible depuis n'importe quel appareil) ──
            $ngrokUrl  = rtrim($_ENV['NGROK_URL'] ?? 'http://localhost', '/');
            $billetUrl = $ngrokUrl . '/reservation-act/billet/' . $IDRes;

            // ── Rendu du template Twig email ──
            $htmlContent = $this->renderView('emails/reservation_confirmation.html.twig', [
                'nom'           => $nom,
                'prenom'        => $prenom,
                'titreActivite' => $titreActivite,
                'nombrePlaces'  => $nombrePlaces,
                'prixTotal'     => $prixTotal,
                'billetUrl'     => $billetUrl,
            ]);

            $brevo = new \Brevo\Brevo($apiKey);

            $sender = new SendTransacEmailRequestSender([
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
        $code = trim($request->query->get('code', ''));

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
        Request             $request,
        Connection          $connection,
        ValidatorInterface  $validator,
        CodePromoRepository $codePromoRepository,
        int                 $IDAct
    ): Response {
        /** @var \App\Entity\User|null $user */
         $user   = $this->getUser();
        $userId = $user instanceof \App\Entity\User ? $user->getId() : null;
        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$IDAct]
        );

        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        $placesReservees   = (int) $connection->fetchOne(
            "SELECT COALESCE(SUM(NombrePlaces), 0) FROM ReservationAct WHERE IDAct = ?",
            [$IDAct]
        );
        $placesDisponibles = (int) $activite['CapaciteM'] - $placesReservees;

        $errors = [];
        $old    = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'nombrePlaces' => ''];
        if ($user instanceof \App\Entity\User) {
            $old['nom']    = $user->getNom();
            $old['prenom'] = $user->getPrenom();
            $old['email']  = $user->getEmail();
         }

        if ($request->isMethod('POST')) {

            $nom        = trim($request->request->get('nom', ''));
            $prenom     = trim($request->request->get('prenom', ''));
            $email      = trim($request->request->get('email', ''));
            $telephone  = trim($request->request->get('telephone', ''));
            $rawPlaces  = trim($request->request->get('nombrePlaces', ''));
            $codePromo  = trim(strtoupper($request->request->get('codePromo', '')));
            $promoReduc = (int) $request->request->get('promoReduction', 0);

            $old = compact('nom', 'prenom', 'email', 'telephone') + ['nombrePlaces' => $rawPlaces];

            $reservation = new ReservationAct();
            $reservation->setNom($nom);
            $reservation->setPrenom($prenom);
            $reservation->setEmail($email);
            $reservation->setTelephone($telephone);

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

            // ── Réponse AJAX ──────────────────────────────────────────────────
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                if (empty($errors)) {
                    $nombrePlaces = (int) $rawPlaces;
                    $prixBase     = (float) $activite['Prix'] * $nombrePlaces;
                    $prixTotal    = $prixBase * (1 - $reductionValidee / 100);

                    $connection->executeStatement(
                        "INSERT INTO ReservationAct (id, IDAct, Nom, Prenom, email, telephone, DateReservation, NombrePlaces, Prix)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$userId, $IDAct, $nom, $prenom, $email, $telephone, date('Y-m-d'), $nombrePlaces, round($prixTotal, 2)]
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

            // ── Fallback soumission classique ─────────────────────────────────
            if (empty($errors)) {
                $nombrePlaces = (int) $rawPlaces;
                $prixBase     = (float) $activite['Prix'] * $nombrePlaces;
                $prixTotal    = $prixBase * (1 - $reductionValidee / 100);

                $connection->executeStatement(
                    "INSERT INTO ReservationAct (id, IDAct, Nom, Prenom, email, telephone, DateReservation, NombrePlaces, Prix)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$userId, $IDAct, $nom, $prenom, $email, $telephone, date('Y-m-d'), $nombrePlaces, round($prixTotal, 2)]
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

        // Récupération de la clé API pour le template
        $apiKey = $_ENV['EXCHANGERATE_API_KEY'] ?? '';

        return $this->render('activite/reservationact.html.twig', [
            'activite'           => $activite,
            'errors'             => $errors,
            'old'                => $old,
            'placesDisponibles'  => $placesDisponibles,
            'exchangeRateApiKey' => $apiKey,
        ]);
    }
}