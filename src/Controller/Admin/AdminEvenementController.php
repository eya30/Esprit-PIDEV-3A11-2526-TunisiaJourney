<?php

namespace App\Controller\Admin;

use App\Entity\Evenement;
use Doctrine\DBAL\Connection;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/evenements')]
class AdminEvenementController extends AbstractController
{
    const ITEMS_PER_PAGE = 4;
    const ITEMS_PER_PAGE_ACTIVITES = 2;

    private function parseDate(string $raw): ?\DateTime
    {
        $raw = trim($raw);
        if ($raw === '') return null;
        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $raw)) {
            $d = \DateTime::createFromFormat('d/m/Y', $raw);
            return $d ?: null;
        }
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $raw)) {
            $d = \DateTime::createFromFormat('Y-m-d', $raw);
            return $d ?: null;
        }
        return null;
    }

    private function normalizeDate(?\DateTime $date): ?string
    {
        return $date ? $date->format('Y-m-d') : null;
    }

    private function buildErrorsArray(iterable $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath();
            $path = match($path) {
                'Titre'        => 'titre',
                'Description'  => 'description',
                'Lieu'         => 'lieu',
                'DateDebut'    => 'dateDebut',
                'DateFin'      => 'dateFin',
                'CapaciteMax'  => 'capaciteMax',
                'Organisateur' => 'organisateur',
                default        => $path
            };
            if (!isset($errors[$path])) {
                $errors[$path] = $violation->getMessage();
            }
        }
        return $errors;
    }

    private function buildSearchQuery(string $search): array
    {
        $where  = '';
        $params = [];

        if ($search !== '') {
            $like         = '%' . $search . '%';
            $isDateSearch = preg_match('#^\d{2}/\d{2}/\d{2,4}$#', $search);

            if ($isDateSearch) {
                $parts = explode('/', $search);
                if (count($parts) == 3) {
                    $day        = $parts[0];
                    $month      = $parts[1];
                    $year       = strlen($parts[2]) == 2 ? '20' . $parts[2] : $parts[2];
                    $dateSearch = $year . '-' . $month . '-' . $day;
                    $where      = "WHERE (Titre LIKE ? OR Description LIKE ? OR Lieu LIKE ?
                                   OR Organisateur LIKE ? OR DateDebut LIKE ? OR DateFin LIKE ?
                                   OR CAST(CapaciteMax AS CHAR) LIKE ?
                                   OR DateDebut = ? OR DateFin = ?)";
                    $params     = [$like, $like, $like, $like, $like, $like, $like, $dateSearch, $dateSearch];
                } else {
                    $where  = "WHERE (Titre LIKE ? OR Description LIKE ? OR Lieu LIKE ?
                               OR Organisateur LIKE ? OR DateDebut LIKE ? OR DateFin LIKE ?
                               OR CAST(CapaciteMax AS CHAR) LIKE ?)";
                    $params = [$like, $like, $like, $like, $like, $like, $like];
                }
            } else {
                $where  = "WHERE (Titre LIKE ? OR Description LIKE ? OR Lieu LIKE ?
                           OR Organisateur LIKE ? OR DateDebut LIKE ? OR DateFin LIKE ?
                           OR CAST(CapaciteMax AS CHAR) LIKE ?)";
                $params = [$like, $like, $like, $like, $like, $like, $like];
            }
        }

        return [$where, $params];
    }

    private function buildOrderBy(string $sort): string
    {
        return match ($sort) {
            'capacite_asc'    => 'ORDER BY CapaciteMax ASC',
            'titre_az'        => 'ORDER BY Titre ASC',
            'organisateur_az' => 'ORDER BY Organisateur ASC',
            default           => 'ORDER BY DateDebut ASC',
        };
    }

    #[Route('/', name: 'admin_evenement_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page   = max(1, $request->query->getInt('page', 1));
        $search = trim($request->query->get('search', ''));
        $sort   = $request->query->get('sort', 'date_asc');
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        [$where, $params] = $this->buildSearchQuery($search);
        $orderBy          = $this->buildOrderBy($sort);

        $total      = (int) $connection->fetchOne("SELECT COUNT(*) FROM Evenement $where", $params);
        $totalPages = max(1, ceil($total / self::ITEMS_PER_PAGE));

        if ($page > $totalPages && $totalPages > 0) {
            return $this->redirectToRoute('admin_evenement_index', [
                'page'   => $totalPages,
                'search' => $search,
                'sort'   => $sort,
            ]);
        }

        $sql = "SELECT * FROM Evenement $where $orderBy LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)$offset;

        $evenements = array_map(
            fn($row) => array_change_key_case($row, CASE_LOWER),
            $connection->fetchAllAssociative($sql, $params)
        );

        $response = $this->render('admin/evenement/admin_evenement_index.html.twig', [
            'evenements'   => $evenements,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'total_items'  => $total,
            'search'       => $search,
            'sort'         => $sort,
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    #[Route('/fullcalendar/events', name: 'admin_fullcalendar_events')]
    public function fullCalendarEvents(Connection $connection, Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end   = $request->query->get('end');

        $evenements = $connection->fetchAllAssociative(
            "SELECT IDEv, Titre, DateDebut, DateFin, Lieu FROM Evenement
              WHERE DateDebut <= ? AND (DateFin >= ? OR DateFin IS NULL)",
            [$end ?? '9999-12-31', $start ?? '0000-01-01']
        );

        $events = [];
        foreach ($evenements as $ev) {
            // DateFin inclusive → FullCalendar veut exclusive, on ajoute 1 jour
            $endDate = $ev['DateFin']
                ? (new \DateTime($ev['DateFin']))->modify('+1 day')->format('Y-m-d')
                : (new \DateTime($ev['DateDebut']))->modify('+1 day')->format('Y-m-d');

            $events[] = [
                'id'              => $ev['IDEv'],
                'title'           => $ev['Titre'],
                'start'           => $ev['DateDebut'],
                'end'             => $endDate,
                'url'             => $this->generateUrl('admin_evenement_edit', ['id' => $ev['IDEv']]),
                'backgroundColor' => '#93032E',
                'borderColor'     => '#6B0221',
                'textColor'       => '#ffffff',
                'extendedProps'   => ['lieu' => $ev['Lieu'] ?? ''],
            ];
        }

        return new JsonResponse($events);
    }

    #[Route('/export-pdf', name: 'admin_evenement_export_pdf')]
    public function exportPdf(Connection $connection, Request $request): Response
    {
        $search  = trim($request->query->get('search', ''));
        $sort    = $request->query->get('sort', 'date_asc');

        [$where, $params] = $this->buildSearchQuery($search);
        $orderBy          = $this->buildOrderBy($sort);

        $evenements = array_map(
            fn($row) => array_change_key_case($row, CASE_LOWER),
            $connection->fetchAllAssociative("SELECT * FROM Evenement $where $orderBy", $params)
        );

        foreach ($evenements as &$ev) {
            $ev['datedebut_fmt']     = $ev['datedebut']
                ? (new \DateTime($ev['datedebut']))->format('d/m/Y') : '—';
            $ev['datefin_fmt']       = $ev['datefin']
                ? (new \DateTime($ev['datefin']))->format('d/m/Y') : '—';
            $ev['description_short'] = mb_strimwidth($ev['description'] ?? '', 0, 110, '…');
        }
        unset($ev);

        $now  = new \DateTime();
        $html = $this->renderView('admin/evenement/exportev_pdf.html.twig', [
            'evenements'   => $evenements,
            'generated_at' => $now,
            'total'        => count($evenements),
            'search'       => $search,
            'sort'         => $sort,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'evenements_' . $now->format('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    // Remplacez la méthode new() par celle-ci :

#[Route('/new', name: 'admin_evenement_new', methods: ['GET', 'POST'])]
public function new(Request $request, Connection $connection, ValidatorInterface $validator): Response
{
    $errors = [];
    $old    = [];

    if ($request->isMethod('POST')) {
        $titreRaw        = $request->request->get('titre', '');
        $descriptionRaw  = $request->request->get('description', '');
        $lieuRaw         = $request->request->get('lieu', '');
        $capaciteRaw     = $request->request->get('capaciteMax', '');
        $dateDebutRaw    = $request->request->get('dateDebut', '');
        $dateFinRaw      = $request->request->get('dateFin', '');
        $organisateurRaw = $request->request->get('organisateur', '');

        $old = [
            'titre'        => $titreRaw,
            'description'  => $descriptionRaw,
            'lieu'         => $lieuRaw,
            'dateDebut'    => $dateDebutRaw,
            'dateFin'      => $dateFinRaw,
            'capaciteMax'  => $capaciteRaw,
            'organisateur' => $organisateurRaw,
        ];

        $dateDebut   = $this->parseDate($dateDebutRaw);
        $dateFin     = $this->parseDate($dateFinRaw);
        $capaciteInt = ($capaciteRaw !== '' && ctype_digit($capaciteRaw))
                       ? (int)$capaciteRaw : null;

        $evenement = new Evenement();
        $evenement->setTitre(trim($titreRaw));
        $evenement->setDescription(trim($descriptionRaw));
        $evenement->setLieu(trim($lieuRaw));
        $evenement->setCapaciteMax($capaciteInt);
        $evenement->setDateDebut($dateDebut);
        $evenement->setDateFin($dateFin);
        $evenement->setOrganisateur(trim($organisateurRaw));
        $evenement->setUserId('1');

        $violations = $validator->validate($evenement);
        $errors     = $this->buildErrorsArray($violations);

        if ($dateDebutRaw !== '' && $dateDebut === null && !isset($errors['dateDebut']))
            $errors['dateDebut'] = 'La date de début doit être au format JJ/MM/AAAA.';
        if ($dateFinRaw !== '' && $dateFin === null && !isset($errors['dateFin']))
            $errors['dateFin'] = 'La date de fin doit être au format JJ/MM/AAAA.';
        if ($capaciteRaw !== '' && !ctype_digit($capaciteRaw) && !isset($errors['capaciteMax']))
            $errors['capaciteMax'] = 'La capacité doit être un entier valide (chiffres uniquement).';

        if ($request->isXmlHttpRequest() || $request->request->get('ajax_validation'))
            return $this->json(['errors' => $errors]);

        if (empty($errors)) {
            $imageFile = $request->files->get('image');
            $imageName = null;
            if ($imageFile && $imageFile->getError() !== UPLOAD_ERR_NO_FILE) {
                $safeFilename = preg_replace(
                    '/[^a-zA-Z0-9]/', '_',
                    pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)
                );
                $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_directory'), $imageName);
            }
            $connection->executeStatement(
                "INSERT INTO Evenement (Titre, Description, DateDebut, DateFin, Lieu, CapaciteMax, Image, Organisateur, id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $evenement->getTitre(),
                    $evenement->getDescription(),
                    $this->normalizeDate($dateDebut),
                    $this->normalizeDate($dateFin),
                    $evenement->getLieu(),
                    $evenement->getCapaciteMax(),
                    $imageName,
                    $evenement->getOrganisateur(),
                    '1',
                ]
            );
            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_evenement_index');
        }
    }

    // Pré-remplir la date si elle vient du calendrier
    $dateFromCalendar = $request->query->get('dateDebut', '');
    if ($dateFromCalendar && empty($old['dateDebut'])) {
        $d = \DateTime::createFromFormat('Y-m-d', $dateFromCalendar);
        if ($d) $old['dateDebut'] = $d->format('d/m/Y');
    }

    return $this->render('admin/evenement/newev.html.twig', [
        'errors' => $errors,
        'old'    => $old,
    ]);
}

    #[Route('/{id}/edit', name: 'admin_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, ValidatorInterface $validator, int $id): Response
    {
        $evenementData = $connection->fetchAssociative("SELECT * FROM Evenement WHERE IDEv = ?", [$id]);
        if (!$evenementData) throw $this->createNotFoundException('Événement non trouvé');

        $errors = [];
        $old    = [];

        if ($request->isMethod('POST')) {
            $titreRaw        = $request->request->get('titre', '');
            $descriptionRaw  = $request->request->get('description', '');
            $lieuRaw         = $request->request->get('lieu', '');
            $capaciteRaw     = $request->request->get('capaciteMax', '');
            $dateDebutRaw    = $request->request->get('dateDebut', '');
            $dateFinRaw      = $request->request->get('dateFin', '');
            $organisateurRaw = $request->request->get('organisateur', '');

            $old = [
                'titre'        => $titreRaw,
                'description'  => $descriptionRaw,
                'lieu'         => $lieuRaw,
                'dateDebut'    => $dateDebutRaw,
                'dateFin'      => $dateFinRaw,
                'capaciteMax'  => $capaciteRaw,
                'organisateur' => $organisateurRaw,
                'image'        => $evenementData['Image'],
            ];

            $dateDebut   = $this->parseDate($dateDebutRaw);
            $dateFin     = $this->parseDate($dateFinRaw);
            $capaciteInt = ($capaciteRaw !== '' && ctype_digit($capaciteRaw))
                           ? (int)$capaciteRaw : null;

            $evenement = new Evenement();
            $evenement->setTitre(trim($titreRaw));
            $evenement->setDescription(trim($descriptionRaw));
            $evenement->setLieu(trim($lieuRaw));
            $evenement->setCapaciteMax($capaciteInt);
            $evenement->setDateDebut($dateDebut);
            $evenement->setDateFin($dateFin);
            $evenement->setOrganisateur(trim($organisateurRaw));

            $violations = $validator->validate($evenement);
            $errors     = $this->buildErrorsArray($violations);

            if ($dateDebutRaw !== '' && $dateDebut === null && !isset($errors['dateDebut']))
                $errors['dateDebut'] = 'La date de début doit être au format JJ/MM/AAAA.';
            if ($dateFinRaw !== '' && $dateFin === null && !isset($errors['dateFin']))
                $errors['dateFin'] = 'La date de fin doit être au format JJ/MM/AAAA.';
            if ($capaciteRaw !== '' && !ctype_digit($capaciteRaw) && !isset($errors['capaciteMax']))
                $errors['capaciteMax'] = 'La capacité doit être un entier valide (chiffres uniquement).';

            if ($request->isXmlHttpRequest() || $request->request->get('ajax_validation'))
                return $this->json(['errors' => $errors]);

            if (empty($errors)) {
                $imageName = $evenementData['Image'];
                $imageFile = $request->files->get('image');
                if ($imageFile && $imageFile->getError() !== UPLOAD_ERR_NO_FILE) {
                    if ($imageName && file_exists($this->getParameter('uploads_directory') . '/' . $imageName))
                        unlink($this->getParameter('uploads_directory') . '/' . $imageName);
                    $safeFilename = preg_replace(
                        '/[^a-zA-Z0-9]/', '_',
                        pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)
                    );
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_directory'), $imageName);
                }
                $connection->executeStatement(
                    "UPDATE Evenement
                        SET Titre=?, Description=?, DateDebut=?, DateFin=?,
                            Lieu=?, CapaciteMax=?, Image=?, Organisateur=?
                      WHERE IDEv=?",
                    [
                        $evenement->getTitre(),
                        $evenement->getDescription(),
                        $this->normalizeDate($dateDebut),
                        $this->normalizeDate($dateFin),
                        $evenement->getLieu(),
                        $evenement->getCapaciteMax(),
                        $imageName,
                        $evenement->getOrganisateur(),
                        $id,
                    ]
                );
                $this->addFlash('success', 'Événement modifié avec succès !');
                return $this->redirectToRoute('admin_evenement_index');
            }
        }

        return $this->render('admin/evenement/editev.html.twig', [
            'evenement'   => $old ?: $evenementData,
            'evenementId' => $id,
            'errors'      => $errors,
            'old'         => $old,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete_evenement_' . $id, $request->request->get('_token'))) {
            $evenement = $connection->fetchAssociative("SELECT Image FROM Evenement WHERE IDEv = ?", [$id]);
            if ($evenement && $evenement['Image']
                && file_exists($this->getParameter('uploads_directory') . '/' . $evenement['Image']))
                unlink($this->getParameter('uploads_directory') . '/' . $evenement['Image']);
            $connection->executeStatement("DELETE FROM Evenement WHERE IDEv = ?", [$id]);
            $this->addFlash('success', 'Événement supprimé avec succès !');
        }
        return $this->redirectToRoute('admin_evenement_index');
    }

    #[Route('/{id}/activites', name: 'admin_evenement_activites')]
    public function activites(Connection $connection, int $id, Request $request): Response
    {
        $evenement = array_change_key_case(
            $connection->fetchAssociative("SELECT * FROM Evenement WHERE IDEv = ?", [$id]),
            CASE_LOWER
        );
        if (!$evenement) throw $this->createNotFoundException('Événement non trouvé');

        $search       = trim($request->query->get('search', ''));
        $sort         = $request->query->get('sort', 'horaire_asc');
        $page         = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = self::ITEMS_PER_PAGE_ACTIVITES;
        $offset       = ($page - 1) * $itemsPerPage;
        $where        = '';
        $params       = [$id];

        if ($search !== '') {
            $searchLower = '%' . strtolower($search) . '%';
            $where       = "AND (LOWER(a.Titre) LIKE ? OR LOWER(a.TypeActivite) LIKE ?
                             OR LOWER(a.NomAnimateur) LIKE ? OR LOWER(a.Duree) LIKE ?
                             OR CAST(a.Prix AS CHAR) LIKE ? OR CAST(a.CapaciteM AS CHAR) LIKE ?)";
            $params      = array_merge($params, [
                $searchLower, $searchLower, $searchLower,
                $searchLower, $searchLower, $searchLower
            ]);
        }

        $activitesRaw = $connection->fetchAllAssociative(
            "SELECT a.* FROM Activite a WHERE a.IDEv = ? $where", $params
        );
        $activites = array_map(fn($row) => array_change_key_case($row, CASE_LOWER), $activitesRaw);

        usort($activites, function ($a, $b) use ($sort) {
            return match ($sort) {
                'capacite_asc' => ($a['capacitem'] ?? 0) <=> ($b['capacitem'] ?? 0),
                'titre_az'     => strcasecmp($a['titre'] ?? '', $b['titre'] ?? ''),
                'prix_asc'     => ($a['prix'] ?? 0) <=> ($b['prix'] ?? 0),
                'type_az'      => strcasecmp($a['typeactivite'] ?? '', $b['typeactivite'] ?? ''),
                default        => ($a['heuredebut'] ?? '00:00') <=> ($b['heuredebut'] ?? '00:00'),
            };
        });

        $total      = count($activites);
        $totalPages = max(1, ceil($total / $itemsPerPage));

        if ($page > $totalPages && $totalPages > 0) {
            return $this->redirectToRoute('admin_evenement_activites', [
                'id'     => $id,
                'page'   => $totalPages,
                'search' => $search,
                'sort'   => $sort,
            ]);
        }

        return $this->render('admin/evenement/activites.html.twig', [
            'evenement'    => $evenement,
            'activites'    => array_slice($activites, $offset, $itemsPerPage),
            'search'       => $search,
            'sort'         => $sort,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'total_items'  => $total,
        ]);
    }
}
