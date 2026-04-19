<?php

namespace App\Controller\Admin;

use App\Service\OllamaService;
use Doctrine\DBAL\Connection;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/programmes')]
class AdminProgrammeController extends AbstractController
{
    const ITEMS_PER_PAGE = 10;

    // ══════════════════════════════════════════════════════════════════════
    //  INDEX
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/', name: 'admin_programme_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page   = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        $searchCondition = '';
        $params = [];

        if (!empty($search)) {
            $searchCondition = ' WHERE (p.nom LIKE :search OR p.lieu LIKE :search OR p.hotel LIKE :search OR v.nom LIKE :search) ';
            $params['search'] = "%$search%";
        }

        $totalProgrammes = $connection->fetchOne("
            SELECT COUNT(*) FROM programmes p
            LEFT JOIN voyages v ON p.idV = v.idV
            $searchCondition
        ", $params);

        $totalPages = max(1, ceil($totalProgrammes / self::ITEMS_PER_PAGE));

        $programmes = $connection->fetchAllAssociative("
            SELECT p.*, v.nom as voyage_nom
            FROM programmes p
            LEFT JOIN voyages v ON p.idV = v.idV
            $searchCondition
            ORDER BY p.idProg DESC
            LIMIT " . (int) self::ITEMS_PER_PAGE . " OFFSET " . (int) $offset,
            $params
        );

        $voyages = $connection->fetchAllAssociative("SELECT idV, nom FROM voyages ORDER BY nom");

        return $this->render('admin/programme/index.html.twig', [
            'programmes'   => $programmes,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'search'       => $search,
            'total_count'  => $totalProgrammes,
            'voyages'      => $voyages,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AI GENERATE  (AJAX)
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/ai-generate', name: 'admin_programme_ai_generate', methods: ['POST'])]
    public function aiGenerate(Request $request, OllamaService $ollama): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'AJAX only'], 400);
        }

        $body  = json_decode($request->getContent(), true);
        $input = trim($body['input'] ?? '');

        if (empty($input)) {
            return new JsonResponse(['error' => 'Input vide'], 400);
        }

        // ── 1. Durée ──────────────────────────────────────────────────────
        $duration = 3;
        if (preg_match('/(\d+)\s*(jour|nuit|day)/i', $input, $m)) {
            $duration = (int) $m[1];
        } elseif (preg_match('/week[- ]?end|2\s*jour/i', $input)) {
            $duration = 2;
        } elseif (preg_match('/semaine/i', $input)) {
            $duration = 7;
        }

        // ── 2. Dates (prochain vendredi → vendredi + durée) ───────────────
        $today           = new \DateTime();
        $dayOfWeek       = (int) $today->format('N');          // 1=lun … 7=dim
        $daysUntilFriday = ((5 - $dayOfWeek + 7) % 7) ?: 7;
        $dateDebut       = (clone $today)->modify("+{$daysUntilFriday} days");
        $dateFin         = (clone $dateDebut)->modify("+{$duration} days");

        // ── 3. Lieu ───────────────────────────────────────────────────────
        $lieuMap = [
            'sahara'    => 'Douz, Grand Erg Oriental',
            'djerba'    => 'Djerba, Houmt Souk',
            'kairouan'  => 'Kairouan, Médina',
            'sidi bou'  => 'Sidi Bou Saïd, Tunis',
            'tabarka'   => 'Tabarka, Aïn Draham',
            'sousse'    => 'Sousse, Médina',
            'hammamet'  => 'Hammamet',
            'tunis'     => 'Tunis, Médina',
            'tozeur'    => 'Tozeur, Chott el-Jérid',
            'monastir'  => 'Monastir',
            'sfax'      => 'Sfax',
            'carthage'  => 'Carthage, Tunis',
            'nabeul'    => 'Nabeul, Cap Bon',
            'bizerte'   => 'Bizerte',
            'matmata'   => 'Matmata, Sud Tunisien',
        ];
        $inputLower = mb_strtolower($input);
        $lieu = 'Tunisie';
        foreach ($lieuMap as $key => $val) {
            if (str_contains($inputLower, $key)) {
                $lieu = $val;
                break;
            }
        }

        // ── 4. Hôtel ──────────────────────────────────────────────────────
        $hotel = match (true) {
            str_contains($inputLower, 'sahara'),
            str_contains($inputLower, 'désert'),
            str_contains($inputLower, 'dune')    => 'Campement Saharien / Dar Zahra',
            str_contains($inputLower, 'djerba')  => 'Hôtel Hasdrubal Thalassa ★★★★',
            str_contains($inputLower, 'tabarka'),
            str_contains($inputLower, 'plongée') => 'Hôtel Tabarka Beach ★★★',
            str_contains($inputLower, 'sidi bou')=> 'Dar Said Boutique Hotel ★★★★',
            str_contains($inputLower, 'hammamet')=> 'Hôtel Sindbad Hammamet ★★★★',
            str_contains($inputLower, 'sousse'),
            str_contains($inputLower, 'monastir')=> 'Hôtel Marhaba Beach ★★★★',
            str_contains($inputLower, 'tozeur')  => 'Hôtel Dar Cherait ★★★★',
            str_contains($inputLower, 'matmata') => 'Hôtel Sidi Driss (troglodyte) ★★★',
            str_contains($inputLower, 'bizerte') => 'Hôtel Nador Bizerte ★★★',
            default                              => 'Hôtel 3★ Centre-ville',
        };

        // ── 5. Description via Ollama ─────────────────────────────────────
        $description = '';
        if ($ollama->isAvailable()) {
            $description = $ollama->generateDescription($input . ' à ' . $lieu, []);
        }
        if (empty(trim($description))) {
            $description = "Partez à la découverte de {$lieu} lors de ce programme de {$duration} jours. "
                         . "Entre paysages authentiques et culture locale, une expérience inoubliable vous attend.";
        }

        // ── 6. Activités via Ollama ───────────────────────────────────────
        $activites = '';
        if ($ollama->isAvailable()) {
            $activites = $ollama->generateInsights(
                $input,
                "Génère 3 à 4 activités touristiques typiques pour un voyage à {$lieu} en Tunisie. "
                . "Réponds UNIQUEMENT avec les activités séparées par des virgules, rien d'autre. "
                . "Exemple: Balade en chameau, Visite de la médina, Dîner traditionnel"
            );
        }
        if (empty(trim($activites ?? ''))) {
            $activites = match (true) {
                str_contains($inputLower, 'sahara'),
                str_contains($inputLower, 'désert')  => 'Balade en chameau, Nuit sous tente, Dîner saharien, Lever de soleil sur les dunes',
                str_contains($inputLower, 'djerba')  => 'Visite du marché, Plage de Sidi Mahrez, Tour de l\'île en vélo, Dégustation de fruits de mer',
                str_contains($inputLower, 'tabarka'),
                str_contains($inputLower, 'plongée') => 'Plongée sous-marine, Randonnée en forêt, Visite du Château Génois, Snorkeling',
                str_contains($inputLower, 'kairouan')=> 'Visite de la Grande Mosquée, Tour de la médina, Musée des Arts islamiques, Dégustation makroudh',
                str_contains($inputLower, 'sidi bou')=> 'Promenade dans les ruelles, Visite du café des Nattes, Coucher de soleil sur la Méditerranée, Galeries d\'art',
                str_contains($inputLower, 'tozeur'),
                str_contains($inputLower, 'chott')   => 'Excursion sur le Chott el-Jérid, Village de sel, Oasis de Chebika, Coucher de soleil',
                default                              => 'Visite guidée, Dégustation culinaire, Balade découverte, Rencontre avec les artisans locaux',
            };
        }

        return new JsonResponse([
            'nom'              => ucfirst($input),
            'description'      => trim($description),
            'dateDebut'        => $dateDebut->format('Y-m-d'),
            'dateFin'          => $dateFin->format('Y-m-d'),
            'lieu'             => $lieu,
            'hotel'            => $hotel,
            'activiteAssociee' => trim($activites),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  NEW
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/new', name: 'admin_programme_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection, ValidatorInterface $validator): Response
    {
        $voyageId = $request->query->get('voyage_id');
        $errors   = [];

        if ($request->isMethod('POST')) {
            $nom             = trim($request->request->get('nom', ''));
            $description     = trim($request->request->get('description', ''));
            $dateDebut       = $request->request->get('dateDebut', '');
            $dateFin         = $request->request->get('dateFin', '');
            $lieu            = trim($request->request->get('lieu', ''));
            $activiteAssociee= trim($request->request->get('activiteAssociee', ''));
            $hotel           = trim($request->request->get('hotel', ''));
            $idV             = $request->request->get('idV', '');

            // Validations
            if (empty($nom)) {
                $errors['nom'] = 'Le nom du programme est obligatoire.';
            } elseif (preg_match('/[0-9]/', $nom)) {
                $errors['nom'] = 'Le nom ne doit pas contenir de chiffres.';
            }

            if (empty($description)) {
                $errors['description'] = 'La description est obligatoire.';
            }

            if (empty($dateDebut)) {
                $errors['dateDebut'] = 'La date de début est obligatoire.';
            }

            if (empty($dateFin)) {
                $errors['dateFin'] = 'La date de fin est obligatoire.';
            }

            if (!empty($dateDebut) && !empty($dateFin)) {
                $dateDebutObj = \DateTime::createFromFormat('Y-m-d', $dateDebut);
                $dateFinObj   = \DateTime::createFromFormat('Y-m-d', $dateFin);
                if ($dateDebutObj && $dateFinObj) {
                    if ($dateFinObj <= $dateDebutObj) {
                        $errors['dateFin'] = 'La date de fin doit être postérieure à la date de début.';
                    }
                } else {
                    if (!$dateDebutObj) $errors['dateDebut'] = 'Format de date invalide (YYYY-MM-DD).';
                    if (!$dateFinObj)   $errors['dateFin']   = 'Format de date invalide (YYYY-MM-DD).';
                }
            }

            if (empty($lieu))            $errors['lieu']             = 'Le lieu est obligatoire.';
            if (empty($activiteAssociee)) $errors['activiteAssociee'] = 'L\'activité associée est obligatoire.';
            if (empty($hotel))           $errors['hotel']            = 'L\'hôtel est obligatoire.';

            if (empty($idV)) {
                $errors['idV'] = 'Veuillez sélectionner un voyage.';
            } else {
                $voyage = $connection->fetchAssociative("SELECT idV FROM voyages WHERE idV = ?", [$idV]);
                if (!$voyage) {
                    $errors['idV'] = 'Le voyage sélectionné n\'existe pas.';
                }
            }

            // Image
            $imageFile = $request->files->get('image');
            $imageName = null;

            if ($imageFile && $imageFile->isValid()) {
                if ($imageFile->getSize() > 2 * 1024 * 1024) {
                    $errors['image'] = 'L\'image ne doit pas dépasser 2MB.';
                }
                if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])) {
                    $errors['image'] = 'Format d\'image non autorisé (JPG, PNG uniquement).';
                }
                if (!isset($errors['image'])) {
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_',
                        pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_programmes_directory'), $imageName);
                }
            }

            if (count($errors) === 0) {
                $programmeId = 'P' . date('YmdHis') . rand(100, 999);

                $connection->executeStatement("
                    INSERT INTO programmes (idProg, nom, description, dateDebut, dateFin, lieu, activiteAssociee, hotel, image, idV)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [$programmeId, $nom, $description, $dateDebut, $dateFin, $lieu, $activiteAssociee, $hotel, $imageName, $idV]);

                $this->addFlash('success', 'Programme créé avec succès !');
                return $this->redirectToRoute('admin_voyage_programmes', ['id' => $idV]);
            }
        }

        $voyages = $connection->fetchAllAssociative("SELECT idV, nom FROM voyages ORDER BY nom");

        return $this->render('admin/programme/new.html.twig', [
            'errors'          => $errors,
            'voyages'         => $voyages,
            'idV'             => $voyageId,
            'nom'             => $request->request->get('nom', ''),
            'description'     => $request->request->get('description', ''),
            'dateDebut'       => $request->request->get('dateDebut', ''),
            'dateFin'         => $request->request->get('dateFin', ''),
            'lieu'            => $request->request->get('lieu', ''),
            'activiteAssociee'=> $request->request->get('activiteAssociee', ''),
            'hotel'           => $request->request->get('hotel', ''),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EDIT
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/{id}/edit', name: 'admin_programme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, ValidatorInterface $validator, string $id): Response
    {
        $programme = $connection->fetchAssociative("SELECT * FROM programmes WHERE idProg = ?", [$id]);

        if (!$programme) {
            throw $this->createNotFoundException('Programme non trouvé');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $nom             = trim($request->request->get('nom', ''));
            $description     = trim($request->request->get('description', ''));
            $dateDebut       = $request->request->get('dateDebut', '');
            $dateFin         = $request->request->get('dateFin', '');
            $lieu            = trim($request->request->get('lieu', ''));
            $activiteAssociee= trim($request->request->get('activiteAssociee', ''));
            $hotel           = trim($request->request->get('hotel', ''));
            $idV             = $request->request->get('idV', '');

            if (empty($nom)) {
                $errors['nom'] = 'Le nom du programme est obligatoire.';
            } elseif (preg_match('/[0-9]/', $nom)) {
                $errors['nom'] = 'Le nom ne doit pas contenir de chiffres.';
            }

            if (empty($description))     $errors['description']     = 'La description est obligatoire.';
            if (empty($dateDebut))       $errors['dateDebut']       = 'La date de début est obligatoire.';
            if (empty($dateFin))         $errors['dateFin']         = 'La date de fin est obligatoire.';

            if (!empty($dateDebut) && !empty($dateFin)) {
                $dateDebutObj = \DateTime::createFromFormat('Y-m-d', $dateDebut);
                $dateFinObj   = \DateTime::createFromFormat('Y-m-d', $dateFin);
                if ($dateDebutObj && $dateFinObj) {
                    if ($dateFinObj <= $dateDebutObj) {
                        $errors['dateFin'] = 'La date de fin doit être postérieure à la date de début.';
                    }
                } else {
                    if (!$dateDebutObj) $errors['dateDebut'] = 'Format de date invalide (YYYY-MM-DD).';
                    if (!$dateFinObj)   $errors['dateFin']   = 'Format de date invalide (YYYY-MM-DD).';
                }
            }

            if (empty($lieu))            $errors['lieu']             = 'Le lieu est obligatoire.';
            if (empty($activiteAssociee)) $errors['activiteAssociee'] = 'L\'activité associée est obligatoire.';
            if (empty($hotel))           $errors['hotel']            = 'L\'hôtel est obligatoire.';
            if (empty($idV))             $errors['idV']              = 'Veuillez sélectionner un voyage.';

            // Image
            $imageFile = $request->files->get('image');
            $imageName = $programme['image'];

            if ($imageFile && $imageFile->isValid()) {
                if ($imageFile->getSize() > 2 * 1024 * 1024) {
                    $errors['image'] = 'L\'image ne doit pas dépasser 2MB.';
                }
                if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])) {
                    $errors['image'] = 'Format d\'image non autorisé (JPG, PNG uniquement).';
                }
                if (!isset($errors['image'])) {
                    $oldPath = $this->getParameter('uploads_programmes_directory') . '/' . $imageName;
                    if ($imageName && file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_',
                        pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_programmes_directory'), $imageName);
                }
            }

            if (count($errors) === 0) {
                $connection->executeStatement("
                    UPDATE programmes
                    SET nom = ?, description = ?, dateDebut = ?, dateFin = ?, lieu = ?,
                        activiteAssociee = ?, hotel = ?, image = ?, idV = ?
                    WHERE idProg = ?
                ", [$nom, $description, $dateDebut, $dateFin, $lieu, $activiteAssociee, $hotel, $imageName, $idV, $id]);

                $this->addFlash('success', 'Programme modifié avec succès !');
                return $this->redirectToRoute('admin_voyage_programmes', ['id' => $idV]);
            }
        }

        $voyages = $connection->fetchAllAssociative("SELECT idV, nom FROM voyages ORDER BY nom");

        return $this->render('admin/programme/edit.html.twig', [
            'programme'       => $programme,
            'errors'          => $errors,
            'voyages'         => $voyages,
            'nom'             => $request->request->get('nom',             $programme['nom']),
            'description'     => $request->request->get('description',     $programme['description']),
            'dateDebut'       => $request->request->get('dateDebut',       $programme['dateDebut']),
            'dateFin'         => $request->request->get('dateFin',         $programme['dateFin']),
            'lieu'            => $request->request->get('lieu',            $programme['lieu']),
            'activiteAssociee'=> $request->request->get('activiteAssociee',$programme['activiteAssociee']),
            'hotel'           => $request->request->get('hotel',           $programme['hotel']),
            'idV'             => $request->request->get('idV',             $programme['idV']),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  DELETE
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/{id}/delete', name: 'admin_programme_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, string $id): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete_programme_' . $id, $submittedToken)) {
            $programme = $connection->fetchAssociative("SELECT image, idV FROM programmes WHERE idProg = ?", [$id]);

            if ($programme) {
                $imgPath = $this->getParameter('uploads_programmes_directory') . '/' . $programme['image'];
                if ($programme['image'] && file_exists($imgPath)) {
                    unlink($imgPath);
                }

                $connection->executeStatement("DELETE FROM reservationprog WHERE idP = ?", [$id]);
                $connection->executeStatement("DELETE FROM programmes WHERE idProg = ?", [$id]);

                $this->addFlash('success', 'Programme supprimé avec succès !');
                return $this->redirectToRoute('admin_voyage_programmes', ['id' => $programme['idV']]);
            }
        }

        $this->addFlash('error', 'Erreur lors de la suppression.');
        return $this->redirectToRoute('admin_programme_index');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RESERVATIONS
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/{id}/reservations', name: 'admin_programme_reservations')]
    public function reservations(Connection $connection, string $id, Request $request): Response
    {
        $programme = $connection->fetchAssociative("
            SELECT p.*, v.nom as voyage_nom
            FROM programmes p
            LEFT JOIN voyages v ON p.idV = v.idV
            WHERE p.idProg = ?
        ", [$id]);

        if (!$programme) {
            throw $this->createNotFoundException('Programme non trouvé');
        }

        $sort   = $request->query->get('sort', 'date_desc');
        $search = $request->query->get('search', '');

        $sql    = "SELECT rp.*, rp.nom as user_nom, rp.prenom as user_prenom,
                          rp.email as user_email, rp.telephone as user_telephone
                   FROM reservationprog rp
                   WHERE rp.idP = ?";
        $params = [$id];

        if (!empty($search)) {
            $sql .= " AND (rp.nom LIKE ? OR rp.prenom LIKE ? OR rp.email LIKE ? OR rp.telephone LIKE ?)";
            $sp = "%$search%";
            $params = array_merge($params, [$sp, $sp, $sp, $sp]);
        }

        $sql .= match ($sort) {
            'nom_asc'   => ' ORDER BY rp.nom ASC, rp.prenom ASC',
            'nom_desc'  => ' ORDER BY rp.nom DESC, rp.prenom DESC',
            'date_asc'  => ' ORDER BY rp.dateProgramme ASC',
            'prix_asc'  => ' ORDER BY rp.prixProg ASC',
            'prix_desc' => ' ORDER BY rp.prixProg DESC',
            default     => ' ORDER BY rp.dateProgramme DESC',
        };

        $reservations = $connection->fetchAllAssociative($sql, $params);

        if ($request->query->get('ajax')) {
            return $this->json(['reservations' => $reservations, 'total' => count($reservations)]);
        }

        return $this->render('admin/programme/reservations.html.twig', [
            'programme'    => $programme,
            'reservations' => $reservations,
            'sort'         => $sort,
            'search'       => $search,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RESERVATIONS → PDF
    // ══════════════════════════════════════════════════════════════════════

    #[Route('/{id}/reservations/pdf', name: 'admin_programme_reservations_pdf', methods: ['GET'])]
    public function reservationsPdf(Connection $connection, string $id, Request $request): Response
    {
        $programme = $connection->fetchAssociative("
            SELECT p.*, v.nom as voyage_nom
            FROM programmes p
            LEFT JOIN voyages v ON p.idV = v.idV
            WHERE p.idProg = ?
        ", [$id]);

        if (!$programme) {
            throw $this->createNotFoundException('Programme non trouvé');
        }

        $search = $request->query->get('search', '');
        $sql    = "SELECT rp.*, rp.nom as user_nom, rp.prenom as user_prenom,
                          rp.email as user_email, rp.telephone as user_telephone
                   FROM reservationprog rp WHERE rp.idP = ?";
        $params = [$id];

        if (!empty($search)) {
            $sql .= " AND (rp.nom LIKE ? OR rp.prenom LIKE ? OR rp.email LIKE ? OR rp.telephone LIKE ?)";
            $sp = "%$search%";
            $params = array_merge($params, [$sp, $sp, $sp, $sp]);
        }

        $sql .= " ORDER BY rp.dateProgramme DESC";
        $reservations = $connection->fetchAllAssociative($sql, $params);

        $totalReservations = count($reservations);
        $totalPersonnes    = array_sum(array_column($reservations, 'nbre'));
        $totalMontant      = array_sum(array_column($reservations, 'prixProg'));
        $totalPaye         = array_sum(
            array_map(fn($r) => $r['statutPaiement'] === 'payé' ? $r['prixProg'] : 0, $reservations)
        );

        $html = $this->renderView('admin/programme/reservations_pdf.html.twig', [
            'programme'         => $programme,
            'reservations'      => $reservations,
            'total_reservations'=> $totalReservations,
            'total_personnes'   => $totalPersonnes,
            'total_montant'     => $totalMontant,
            'total_paye'        => $totalPaye,
            'generated_at'      => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath($this->getParameter('kernel.project_dir')));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'reservations_' . $programme['idProg'] . '_' . date('Y-m-d_H-i') . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}