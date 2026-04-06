<?php

namespace App\Controller;

use App\Entity\ReservationProg;
use App\Entity\Programme;
use App\Service\BrevoMailerService;
use Doctrine\DBAL\Connection;
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
        Connection $connection,
        ValidatorInterface $validator,
        BrevoMailerService $mailerService = null,
        string $idProg = null
    ): Response {

        if (!$idProg) {
            $this->addFlash('error', 'ID du programme manquant');
            return $this->redirectToRoute('app_voyage_index');
        }

        $programme = $entityManager->getRepository(Programme::class)->find($idProg);

        if (!$programme) {
            $this->addFlash('error', 'Programme non trouvé');
            return $this->redirectToRoute('app_voyage_index');
        }

        $data = $request->request->all();

        $reservation = new ReservationProg();
        $reservation->setNom($data['nom'] ?? '');
        $reservation->setPrenom($data['prenom'] ?? '');
        $reservation->setTelephone($data['telephone'] ?? '');
        $reservation->setEmail($data['email'] ?? '');

        // Gestion sécurisée du champ nbre (texte brut depuis le formulaire)
        $nbreRaw = trim($data['nbre'] ?? '');
        if ($nbreRaw !== '' && ctype_digit($nbreRaw)) {
            $reservation->setNbre((int) $nbreRaw);
        }

        $reservation->setIdP($idProg);
        $reservation->setDateProgramme(new \DateTime());
        $reservation->setStatutPaiement('payé');

        $voyage = $programme->getVoyage();
        $prixTotal = $voyage->getPrix() * ($reservation->getNbre() ?? 1);
        $reservation->setPrixProg((float) $prixTotal);

        // ✅ VALIDATION SYMFONY (annotations sur l'entité)
        $errors = $validator->validate($reservation);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            $source = $request->request->get('source', 'programme_show');

            if ($source === 'voyage_programmes') {
                $voyageId = $request->request->get('voyageId') ?: $programme->getVoyage()->getIdV();
                $voyage = $connection->fetchAssociative('SELECT * FROM voyages WHERE idV = ?', [$voyageId]);
                $programmes = $connection->fetchAllAssociative('SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC', [$voyageId]);

                return $this->render('voyage/programmes.html.twig', [
                    'voyage'    => $voyage,
                    'programmes'=> $programmes,
                    'errors'    => $errorMessages,
                    'formData'  => $data,
                ]);
            }

            return $this->render('programme/show.html.twig', [
                'programme'  => $programme,
                'errors'     => $errorMessages,
                'formData'   => $data,
            ]);
        }

        // Pas d'erreurs → on persiste
        $entityManager->persist($reservation);
        $entityManager->flush();

        // Envoi de l'email de confirmation (optionnel)
        if ($mailerService) {
            try {
                $mailerService->sendConfirmationEmail(
                    $reservation->getEmail(),
                    $reservation->getNom(),
                    $reservation->getPrenom(),
                    [
                        'programme_nom' => $programme->getNom(),
                        'lieu'          => $programme->getLieu(),
                        'date_debut'    => $programme->getDateDebut()->format('d/m/Y'),
                        'date_fin'      => $programme->getDateFin()->format('d/m/Y'),
                        'nbre'          => $reservation->getNbre(),
                        'prix_total'    => $prixTotal,
                        'email'         => $reservation->getEmail(),
                        'telephone'     => $reservation->getTelephone(),
                    ]
                );
            } catch (\Exception $e) {
                // Silencieux : l'email échoue mais la réservation est enregistrée
            }
        }

        $this->addFlash('success', 'Réservation confirmée ! Un email vous a été envoyé.');
        return $this->redirectToRoute('app_voyage_index');
    }
}