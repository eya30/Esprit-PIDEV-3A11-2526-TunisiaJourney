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
        return $this->render('admin/activite/admin_activite_index.html.twig', [
            'activites' => $activites,
        ]);
    }

    #[Route('/new', name: 'app_activite_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activite = new Activite();
        $form = $this->createForm(ActiviteType::class, $activite);
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
            return $this->redirectToRoute('app_activite_index');
        }

        return $this->render('activite/newact.html.twig', ['activite' => $activite, 'form' => $form]);
    }

    #[Route('/{IDAct}', name: 'app_activite_show', methods: ['GET'])]
    public function show(
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
            'action' => $this->generateUrl('app_avis_new', ['IDAct' => $activite->getIDAct()]),
            'method' => 'POST',
        ]);

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
        ]);
    }

    #[Route('/{IDAct}/ai-summary', name: 'app_activite_ai_summary', methods: ['GET'])]
    public function getAISummary(
        Activite $activite,
        AISummaryService $aiSummaryService
    ): JsonResponse {
        $summary = $aiSummaryService->generateSummary($activite->getIDAct());
        
        return $this->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    #[Route('/{IDAct}/avis', name: 'app_avis_new', methods: ['POST'])]
    public function newAvis(
        Request $request,
        Activite $activite,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un avis.');
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
            $noteRaw = $postData[$formName]['note'] ?? '';
            $note = (int) $noteRaw;

            if ($note < 1 || $note > 5) {
                $this->addFlash('error', 'Veuillez sélectionner une note entre 1 et 5.');
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
            return $this->redirectToRoute('app_activite_index');
        }

        return $this->render('activite/editact.html.twig', ['activite' => $activite, 'form' => $form]);
    }

    #[Route('/{IDAct}/delete', name: 'app_activite_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Activite $activite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activite->getIDAct(), $request->request->get('_token'))) {
            $entityManager->remove($activite);
            $entityManager->flush();
            $this->addFlash('success', 'Activité supprimée avec succès !');
        }
        return $this->redirectToRoute('app_activite_index');
    }

    #[Route('/evenement/{IDEv}', name: 'app_activite_by_evenement', methods: ['GET'])]
    public function activitesByEvenement(
        Evenement $evenement,
        ActiviteRepository $activiteRepository,
        Connection $connection
    ): Response {
        $activites = $activiteRepository->findBy(['evenement' => $evenement]);

        if (empty($activites)) {
            return $this->render('evenement/activites_ev.html.twig', [
                'evenement' => $evenement,
                'activites' => [],
                'placesData' => [],
                'typesActivite' => [],
            ]);
        }

        $ids = array_map(fn($a) => $a->getIDAct(), $activites);
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
            ];

            $type = $activite->getTypeActivite();
            if ($type && !in_array($type, $typesActivite)) {
                $typesActivite[] = $type;
            }
        }

        sort($typesActivite);

        return $this->render('evenement/activites_ev.html.twig', [
            'evenement' => $evenement,
            'activites' => $activites,
            'placesData' => $placesData,
            'typesActivite' => $typesActivite,
        ]);
    }
}