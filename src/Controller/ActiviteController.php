<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Entity\AvisAct;
use App\Entity\Evenement;
use App\Entity\ReservationAct;
use App\Form\ActiviteType;
use App\Form\AvisActType;
use App\Repository\ActiviteRepository;
use App\Repository\AvisActRepository;
use App\Repository\ListeAttenteRepository;
use App\Service\AISummaryService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activite')]
class ActiviteController extends AbstractController
{
    #[Route('/', name: 'app_activite_index', methods: ['GET'])]
    public function index(ActiviteRepository $activiteRepository): Response
    {
        $activites = $activiteRepository->findAll();
<<<<<<< HEAD

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return $this->render('admin/activite/admin_activite_index.html.twig', [
            'activites' => $activites,
        ]);
    }

    #[Route('/new', name: 'app_activite_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activite = new Activite();
<<<<<<< HEAD
        $form     = $this->createForm(ActiviteType::class, $activite);
=======
        $form = $this->createForm(ActiviteType::class, $activite);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_activites_directory'), $newFilename);
                $activite->setImage($newFilename);
            }
            $entityManager->persist($activite);
            $entityManager->flush();
            $this->addFlash('success', 'Activité créée avec succès !');
<<<<<<< HEAD

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            return $this->redirectToRoute('app_activite_index');
        }

        return $this->render('activite/newact.html.twig', ['activite' => $activite, 'form' => $form]);
    }

    #[Route('/{IDAct}', name: 'app_activite_show', methods: ['GET'])]
    public function show(
<<<<<<< HEAD
        Activite               $activite,
        Connection             $connection,
        AvisActRepository      $avisRepo,
        ListeAttenteRepository $listeRepo
    ): Response {
        $idAct = $activite->getIDAct();

        $placesReservees = (int) $connection->fetchOne(
            "SELECT COALESCE(SUM(NombrePlaces), 0) FROM ReservationAct WHERE IDAct = ? AND status = 'confirmé'",
            [$idAct]
        );
        $placesDisponibles = $activite->getCapaciteM() - $placesReservees;
        $estComplet        = $placesDisponibles <= 0;

        $estEnListeAttente = false;
        $positionListe     = null;

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // FIX PHPStan :80/:82 — guard that idAct and email are non-null before passing
        if ($user !== null && $estComplet && $idAct !== null) {
            $email = $user->getEmail();
            if ($email !== null) {
                $estEnListeAttente = $listeRepo->estDejaInscrit($idAct, $email);
                if ($estEnListeAttente) {
                    $positionListe = $listeRepo->getPositionDansFile($idAct, $email);
                }
            }
        }

        $avis        = $avisRepo->findByActivite($activite);
        $moyenneNote = $avisRepo->getMoyenneNote($activite);
        $formAvis    = $this->createForm(AvisActType::class, new AvisAct(), [
=======
        Activite          $activite,
        Connection        $connection,
        AvisActRepository $avisRepo,
        ListeAttenteRepository $listeRepo
    ): Response {
        // Compter UNIQUEMENT les réservations CONFIRMEES
        $placesReservees = (int) $connection->fetchOne(
            "SELECT COALESCE(SUM(NombrePlaces), 0) FROM ReservationAct WHERE IDAct = ? AND status = 'confirmé'",
            [$activite->getIDAct()]
        );
        $placesDisponibles = $activite->getCapaciteM() - $placesReservees;
        
        $estComplet = $placesDisponibles <= 0;
        
        $estEnListeAttente = false;
        $positionListe = null;
        
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if ($user && $estComplet) {
            $estEnListeAttente = $listeRepo->estDejaInscrit($activite->getIDAct(), $user->getEmail());
            if ($estEnListeAttente) {
                $positionListe = $listeRepo->getPositionDansFile($activite->getIDAct(), $user->getEmail());
            }
        }

        $avis = $avisRepo->findByActivite($activite);
        $moyenneNote = $avisRepo->getMoyenneNote($activite);
        $formAvis = $this->createForm(AvisActType::class, new AvisAct(), [
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            'action' => $this->generateUrl('app_avis_new', ['IDAct' => $activite->getIDAct()]),
            'method' => 'POST',
        ]);

<<<<<<< HEAD
        $apiKey      = $_ENV['EXCHANGERATE_API_KEY'] ?? '';
        $apiKeyValid = !empty($apiKey);

        return $this->render('activite/showactv.html.twig', [
            'activite'            => $activite,
            'placesDisponibles'   => $placesDisponibles,
            'estComplet'          => $estComplet,
            'estEnListeAttente'   => $estEnListeAttente,
            'positionListe'       => $positionListe,
            'reservation'         => new ReservationAct(),
            'errors'              => [],
            'avis'                => $avis,
            'moyenneNote'         => $moyenneNote,
            'formAvis'            => $formAvis->createView(),
            'exchangeRateApiKey'  => $apiKey,
            'apiKeyValid'         => $apiKeyValid,
=======
        $apiKey = $_ENV['EXCHANGERATE_API_KEY'] ?? '';
        $apiKeyValid = !empty($apiKey);

        return $this->render('activite/showactv.html.twig', [
            'activite' => $activite,
            'placesDisponibles' => $placesDisponibles,
            'estComplet' => $estComplet,
            'estEnListeAttente' => $estEnListeAttente,
            'positionListe' => $positionListe,
            'reservation' => new ReservationAct(),
            'errors' => [],
            'avis' => $avis,
            'moyenneNote' => $moyenneNote,
            'formAvis' => $formAvis->createView(),
            'exchangeRateApiKey' => $apiKey,
            'apiKeyValid' => $apiKeyValid,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        ]);
    }

    #[Route('/{IDAct}/ai-summary', name: 'app_activite_ai_summary', methods: ['GET'])]
    public function getAISummary(
<<<<<<< HEAD
        Activite         $activite,
        AISummaryService $aiSummaryService
    ): JsonResponse {
        // FIX PHPStan :117 — assert non-null before passing
        $idAct = $activite->getIDAct();
        if ($idAct === null) {
            return $this->json(['success' => false, 'summary' => ''], 400);
        }

        $summary = $aiSummaryService->generateSummary($idAct);

        return $this->json(['success' => true, 'summary' => $summary]);
=======
        Activite $activite,
        AISummaryService $aiSummaryService
    ): JsonResponse {
        $summary = $aiSummaryService->generateSummary($activite->getIDAct());
        
        return $this->json([
            'success' => true,
            'summary' => $summary
        ]);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    }

    #[Route('/{IDAct}/avis', name: 'app_avis_new', methods: ['POST'])]
    public function newAvis(
<<<<<<< HEAD
        Request                $request,
        Activite               $activite,
=======
        Request $request,
        Activite $activite,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un avis.');
<<<<<<< HEAD

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            return $this->redirectToRoute('app_activite_show', ['IDAct' => $activite->getIDAct()]);
        }

        $avis = new AvisAct();
        $avis->setActivite($activite);
        $avis->setNom($user->getPrenom() . ' ' . $user->getNom());

        $form = $this->createForm(AvisActType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $postData = $request->request->all();
            $formName = $form->getName();
<<<<<<< HEAD
            $noteRaw  = $postData[$formName]['note'] ?? '';
            $note     = (int) $noteRaw;

            if ($note < 1 || $note > 5) {
                $this->addFlash('error', 'Veuillez sélectionner une note entre 1 et 5.');

=======
            $noteRaw = $postData[$formName]['note'] ?? '';
            $note = (int) $noteRaw;

            if ($note < 1 || $note > 5) {
                $this->addFlash('error', 'Veuillez sélectionner une note entre 1 et 5.');
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                return $this->redirectToRoute('app_activite_show', ['IDAct' => $activite->getIDAct()]);
            }

            $avis->setNote($note);

            if ($form->isValid()) {
                $entityManager->persist($avis);
                $entityManager->flush();
                $this->addFlash('success', 'Votre avis a été publié avec succès !');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        return $this->redirectToRoute('app_activite_show', ['IDAct' => $activite->getIDAct()]);
    }

    #[Route('/{IDAct}/edit', name: 'app_activite_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Activite $activite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_activites_directory'), $newFilename);
                $activite->setImage($newFilename);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Activité modifiée avec succès !');
<<<<<<< HEAD

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            return $this->redirectToRoute('app_activite_index');
        }

        return $this->render('activite/editact.html.twig', ['activite' => $activite, 'form' => $form]);
    }

    #[Route('/{IDAct}/delete', name: 'app_activite_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Activite $activite, EntityManagerInterface $entityManager): Response
    {
<<<<<<< HEAD
        // FIX PHPStan :193 — cast token to string|null
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $activite->getIDAct(), is_string($token) ? $token : null)) {
=======
        if ($this->isCsrfTokenValid('delete' . $activite->getIDAct(), $request->request->get('_token'))) {
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $entityManager->remove($activite);
            $entityManager->flush();
            $this->addFlash('success', 'Activité supprimée avec succès !');
        }
<<<<<<< HEAD

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return $this->redirectToRoute('app_activite_index');
    }

    #[Route('/evenement/{IDEv}', name: 'app_activite_by_evenement', methods: ['GET'])]
    public function activitesByEvenement(
<<<<<<< HEAD
        Evenement          $evenement,
        ActiviteRepository $activiteRepository,
        Connection         $connection
=======
        Evenement $evenement,
        ActiviteRepository $activiteRepository,
        Connection $connection
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    ): Response {
        $activites = $activiteRepository->findBy(['evenement' => $evenement]);

        if (empty($activites)) {
            return $this->render('evenement/activites_ev.html.twig', [
<<<<<<< HEAD
                'evenement'     => $evenement,
                'activites'     => [],
                'placesData'    => [],
=======
                'evenement' => $evenement,
                'activites' => [],
                'placesData' => [],
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                'typesActivite' => [],
            ]);
        }

<<<<<<< HEAD
        $ids          = array_map(fn ($a) => $a->getIDAct(), $activites);
=======
        $ids = array_map(fn($a) => $a->getIDAct(), $activites);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $reservations = $connection->fetchAllAssociative(
            "SELECT IDAct, COALESCE(SUM(NombrePlaces), 0) AS totalReserve
             FROM reservationact
             WHERE IDAct IN ($placeholders) AND status = 'confirmé'
             GROUP BY IDAct",
            $ids
        );

        $reserveMap = [];
        foreach ($reservations as $row) {
<<<<<<< HEAD
            $reserveMap[(int) $row['IDAct']] = (int) $row['totalReserve'];
        }

        $placesData    = [];
        $typesActivite = [];

        foreach ($activites as $activite) {
            $id       = $activite->getIDAct();
            $capacite = $activite->getCapaciteM();
            $reserve  = $reserveMap[$id] ?? 0;

            // ✅ CORRIGÉ : protection contre capacité nulle ou zéro
            if ($capacite <= 0) {
                $placesData[$id] = [
                    'placesRestantes'        => 0,
                    'placesReservees'        => 0,
                    'pourcentageRemplissage' => 0,
                    'disponibilite'          => 'available',
                ];
                $type = $activite->getTypeActivite();
                if ($type && !in_array($type, $typesActivite)) {
                    $typesActivite[] = $type;
                }
                continue;
            }

            $restantes   = max(0, $capacite - $reserve);
            $pourcentage = round(($reserve / $capacite) * 100);

            if ($pourcentage >= 100) {
                $disponibilite = 'soldout';
            } elseif ($pourcentage >= 80) {
                $disponibilite = 'warning';
            } else {
                $disponibilite = 'available';
            }

            $placesData[$id] = [
                'placesRestantes'        => $restantes,
                'placesReservees'        => $reserve,
                'pourcentageRemplissage' => $pourcentage,
                'disponibilite'          => $disponibilite,
=======
            $reserveMap[(int)$row['IDAct']] = (int)$row['totalReserve'];
        }

        $placesData = [];
        $typesActivite = [];

        foreach ($activites as $activite) {
            $id = $activite->getIDAct();
            $capacite = $activite->getCapaciteM();
            $reserve = $reserveMap[$id] ?? 0;
            $restantes = max(0, $capacite - $reserve);
            $pourcentage = $capacite > 0 ? round(($reserve / $capacite) * 100) : 100;

            if ($pourcentage >= 100) $disponibilite = 'soldout';
            elseif ($pourcentage >= 80) $disponibilite = 'warning';
            else $disponibilite = 'available';

            $placesData[$id] = [
                'placesRestantes' => $restantes,
                'placesReservees' => $reserve,
                'pourcentageRemplissage' => $pourcentage,
                'disponibilite' => $disponibilite,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            ];

            $type = $activite->getTypeActivite();
            if ($type && !in_array($type, $typesActivite)) {
                $typesActivite[] = $type;
            }
        }

        sort($typesActivite);

        return $this->render('evenement/activites_ev.html.twig', [
<<<<<<< HEAD
            'evenement'     => $evenement,
            'activites'     => $activites,
            'placesData'    => $placesData,
=======
            'evenement' => $evenement,
            'activites' => $activites,
            'placesData' => $placesData,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            'typesActivite' => $typesActivite,
        ]);
    }
}