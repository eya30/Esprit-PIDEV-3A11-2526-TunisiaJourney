<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\ReservationAct;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/reservation-act')]
class ReservationActController extends AbstractController
{
    #[Route('/new/{IDAct}', name: 'app_reservationact_new', methods: ['GET', 'POST'])]
    public function new(
        Request            $request,
        Connection         $connection,
        ValidatorInterface $validator,
        int                $IDAct
    ): Response {

        // 1. Récupérer l'activité
        $activite = $connection->fetchAssociative(
            "SELECT * FROM Activite WHERE IDAct = ?",
            [$IDAct]
        );

        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        // 2. Places restantes
        $placesReservees   = (int) $connection->fetchOne(
            "SELECT COALESCE(SUM(NombrePlaces), 0) FROM ReservationAct WHERE IDAct = ?",
            [$IDAct]
        );
        $placesDisponibles = (int) $activite['CapaciteM'] - $placesReservees;

        $errors = [];
        $old    = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'nombrePlaces' => ''];

        if ($request->isMethod('POST')) {

            $nom         = trim($request->request->get('nom', ''));
            $prenom      = trim($request->request->get('prenom', ''));
            $email       = trim($request->request->get('email', ''));
            $telephone   = trim($request->request->get('telephone', ''));
            $rawPlaces   = trim($request->request->get('nombrePlaces', ''));

            $old = compact('nom', 'prenom', 'email', 'telephone') + ['nombrePlaces' => $rawPlaces];

            // Construire l'entité et valider avec le Validator Component
            $reservation = new ReservationAct();
            $reservation->setNom($nom);
            $reservation->setPrenom($prenom);
            $reservation->setEmail($email);
            $reservation->setTelephone($telephone);

            if ($rawPlaces !== '' && ctype_digit($rawPlaces)) {
                $reservation->setNombrePlaces((int) $rawPlaces);
            }

            // VALIDATION — Symfony Validator Component uniquement
            $violations = $validator->validate($reservation);

            foreach ($violations as $violation) {
                $field = lcfirst($violation->getPropertyPath());
                // Ne garder que la première erreur par champ
                if (!isset($errors[$field])) {
                    $errors[$field] = $violation->getMessage();
                }
            }

            // Validation contextuelle (places disponibles) — pas dans l'entité car dépend du contexte
            if (
                !isset($errors['nombrePlaces'])
                && $rawPlaces !== ''
                && ctype_digit($rawPlaces)
                && (int) $rawPlaces > $placesDisponibles
            ) {
                $errors['nombrePlaces'] = 'Seulement ' . $placesDisponibles . ' place(s) disponible(s).';
            }

            // ── Réponse AJAX (fetch depuis le template) ──────────────────────
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                if (empty($errors)) {
                    // Insertion
                    $nombrePlaces = (int) $rawPlaces;
                    $prixTotal    = (float) $activite['Prix'] * $nombrePlaces;

                    $connection->executeStatement(
                        "INSERT INTO ReservationAct (id, IDAct, Nom, Prenom, email, telephone, DateReservation, NombrePlaces, Prix)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        ['1', $IDAct, $nom, $prenom, $email, $telephone, date('Y-m-d'), $nombrePlaces, $prixTotal]
                    );

                    return new JsonResponse(['success' => true]);
                }

                // Erreurs → retourner le tableau pour mise à jour en temps réel
                return new JsonResponse(['success' => false, 'errors' => $errors]);
            }

            // ── Soumission classique (sans JS) — fallback ────────────────────
            if (empty($errors)) {
                $nombrePlaces = (int) $rawPlaces;
                $prixTotal    = (float) $activite['Prix'] * $nombrePlaces;

                $connection->executeStatement(
                    "INSERT INTO ReservationAct (id, IDAct, Nom, Prenom, email, telephone, DateReservation, NombrePlaces, Prix)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    ['1', $IDAct, $nom, $prenom, $email, $telephone, date('Y-m-d'), $nombrePlaces, $prixTotal]
                );

                $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');
                return $this->redirectToRoute('app_activite_show', ['IDAct' => $IDAct]);
            }
        }

        return $this->render('activite/reservationact.html.twig', [
            'activite'          => $activite,
            'errors'            => $errors,
            'old'               => $old,
            'placesDisponibles' => $placesDisponibles,
        ]);
    }
}