<?php

namespace App\Controller\Admin;

use App\Entity\Voyage;
use App\Service\OllamaService;
use App\Service\AIReportGenerator;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\AIAnalyzerService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin/voyages')]
class AdminVoyageController extends AbstractController
{
    const ITEMS_PER_PAGE = 4;

    public function __construct(
        private HttpClientInterface $httpClient,
        private OllamaService $ollamaService,
    ) {}

    // =========================================================
    //  INDEX
    // =========================================================
    #[Route('/', name: 'admin_voyage_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page   = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $isAjax = $request->query->get('ajax', 0);
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        $searchCondition = '';
        $params          = [];

        if (!empty($search)) {
            $searchCondition  = ' WHERE (nom LIKE :search OR description LIKE :search) ';
            $params['search'] = "%$search%";
        }

        $totalVoyages = $connection->fetchOne("SELECT COUNT(*) FROM voyages $searchCondition", $params);
        $totalPages   = max(1, ceil($totalVoyages / self::ITEMS_PER_PAGE));

        $voyages = $connection->fetchAllAssociative(
            "SELECT * FROM voyages $searchCondition ORDER BY idV DESC LIMIT " . (int) self::ITEMS_PER_PAGE . " OFFSET " . (int) $offset,
            $params
        );

        if ($isAjax) {
            $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('delete_voyage')->getValue();

            return $this->json([
                'voyages'      => $voyages,
                'total_count'  => $totalVoyages,
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'csrf_token'   => $csrfToken,
            ]);
        }

        return $this->render('admin/voyage/index.html.twig', [
            'voyages'      => $voyages,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'search'       => $search,
            'total_count'  => $totalVoyages,
        ]);
    }

    // =========================================================
    //  NEW
    // =========================================================
    #[Route('/new', name: 'admin_voyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection, ValidatorInterface $validator): Response
    {
        $errors   = [];
        $formData = [
            'nom'          => '',
            'description'  => '',
            'capacite'     => '',
            'prix'         => '',
            'dateCreation' => '',
            'heure'        => '',
        ];

        if ($request->isMethod('POST')) {
            $formData['nom']          = trim((string) $request->request->get('nom', ''));
            $formData['description']  = trim((string) $request->request->get('description', ''));
            $formData['capacite']     = trim((string) $request->request->get('capacite', ''));
            $formData['prix']         = trim((string) $request->request->get('prix', ''));
            $formData['dateCreation'] = trim((string) $request->request->get('dateCreation', ''));
            $formData['heure']        = trim((string) $request->request->get('heure', ''));

            $voyage = new Voyage();
            $voyage->setNom($formData['nom']);
            $voyage->setDescription($formData['description']);

            if ($formData['capacite'] !== '') {
                if (preg_match('/^[0-9]+$/', $formData['capacite'])) {
                    $voyage->setCapacite((int) $formData['capacite']);
                } else {
                    $errors[] = 'La capacité doit être un chiffre entier.';
                }
            }

            if ($formData['prix'] !== '') {
                $prixFormat = str_replace(',', '.', $formData['prix']);
                if (is_numeric($prixFormat)) {
                    $voyage->setPrix((float) $prixFormat);
                } else {
                    $errors[] = 'Le prix doit être une valeur numérique.';
                }
            }

            if ($formData['dateCreation'] !== '') {
                $date = \DateTime::createFromFormat('Y-m-d', $formData['dateCreation']);
                if ($date instanceof \DateTimeInterface) {
                    $voyage->setDateCreation($date);
                } else {
                    $errors[] = 'La date de création doit être au format YYYY-MM-DD.';
                }
            }

            if ($formData['heure'] !== '') {
                $time = \DateTime::createFromFormat('H:i', $formData['heure']);
                if ($time instanceof \DateTimeInterface) {
                    $voyage->setHeure($time);
                } else {
                    $errors[] = "L'heure doit être au format HH:MM.";
                }
            }

            if (count($errors) === 0) {
                $violations = $validator->validate($voyage);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $errors[] = $violation->getMessage();
                    }
                }
            }

            if (count($errors) === 0) {
                $imageFile = $request->files->get('image');
                $imageName = null;

                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename     = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName        = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_directory'), $imageName);
                } else {
                    $imageName = $this->getFreeImage($formData['nom']);
                }

                $connection->executeStatement(
                    "INSERT INTO voyages (nom, description, capacite, prix, dateCreation, heure, image, id_user)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $formData['nom'],
                        $formData['description'],
                        $voyage->getCapacite(),
                        $voyage->getPrix(),
                        $voyage->getDateCreation()?->format('Y-m-d'),
                        $voyage->getHeure()?->format('H:i:s'),
                        $imageName,
                        1,
                    ]
                );

                $this->addFlash('success', 'Voyage créé avec succès !');

                return $this->redirectToRoute('admin_voyage_index');
            }
        }

        return $this->render('admin/voyage/new.html.twig', [
            'errors'       => $errors,
            'nom'          => $formData['nom'],
            'description'  => $formData['description'],
            'capacite'     => $formData['capacite'],
            'prix'         => $formData['prix'],
            'dateCreation' => $formData['dateCreation'],
            'heure'        => $formData['heure'],
        ]);
    }

    // =========================================================
    //  EDIT
    // =========================================================
    #[Route('/{id}/edit', name: 'admin_voyage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, ValidatorInterface $validator, int $id): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$id]);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage non trouvé');
        }

        $errors   = [];
        $formData = [
            'nom'          => $voyage['nom'],
            'description'  => $voyage['description'],
            'capacite'     => $voyage['capacite'],
            'prix'         => $voyage['prix'],
            'dateCreation' => $voyage['dateCreation'],
            'heure'        => $voyage['heure'],
        ];

        if ($request->isMethod('POST')) {
            $formData['nom']          = trim((string) $request->request->get('nom', ''));
            $formData['description']  = trim((string) $request->request->get('description', ''));
            $formData['capacite']     = trim((string) $request->request->get('capacite', ''));
            $formData['prix']         = trim((string) $request->request->get('prix', ''));
            $formData['dateCreation'] = trim((string) $request->request->get('dateCreation', ''));
            $formData['heure']        = trim((string) $request->request->get('heure', ''));

            $voyageEntity = new Voyage();
            $voyageEntity->setNom($formData['nom']);
            $voyageEntity->setDescription($formData['description']);

            if ($formData['capacite'] !== '') {
                if (preg_match('/^[0-9]+$/', $formData['capacite'])) {
                    $voyageEntity->setCapacite((int) $formData['capacite']);
                } else {
                    $errors[] = 'La capacité doit être un chiffre entier.';
                }
            }

            if ($formData['prix'] !== '') {
                $prixFormat = str_replace(',', '.', $formData['prix']);
                if (is_numeric($prixFormat)) {
                    $voyageEntity->setPrix((float) $prixFormat);
                } else {
                    $errors[] = 'Le prix doit être une valeur numérique.';
                }
            }

            if ($formData['dateCreation'] !== '') {
                $date = \DateTime::createFromFormat('Y-m-d', $formData['dateCreation']);
                if ($date instanceof \DateTimeInterface) {
                    $voyageEntity->setDateCreation($date);
                } else {
                    $errors[] = 'La date de création doit être au format YYYY-MM-DD.';
                }
            }

            if ($formData['heure'] !== '') {
                $time = \DateTime::createFromFormat('H:i', $formData['heure']);
                if ($time instanceof \DateTimeInterface) {
                    $voyageEntity->setHeure($time);
                } else {
                    $errors[] = "L'heure doit être au format HH:MM.";
                }
            }

            if (count($errors) === 0) {
                $violations = $validator->validate($voyageEntity);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $errors[] = $violation->getMessage();
                    }
                }
            }

            if (count($errors) === 0) {
                $imageName = $voyage['image'];
                $imageFile = $request->files->get('image');

                if ($imageFile) {
                    if ($imageName && file_exists($this->getParameter('uploads_directory') . '/' . $imageName)) {
                        unlink($this->getParameter('uploads_directory') . '/' . $imageName);
                    }
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename     = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName        = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_directory'), $imageName);
                } elseif (empty($imageName) && !empty($formData['nom'])) {
                    $newImage = $this->getFreeImage($formData['nom']);
                    if ($newImage) {
                        $imageName = $newImage;
                    }
                }

                $connection->executeStatement(
                    "UPDATE voyages SET nom = ?, description = ?, capacite = ?, prix = ?, dateCreation = ?, heure = ?, image = ? WHERE idV = ?",
                    [
                        $formData['nom'],
                        $formData['description'],
                        $voyageEntity->getCapacite(),
                        $voyageEntity->getPrix(),
                        $voyageEntity->getDateCreation()?->format('Y-m-d'),
                        $voyageEntity->getHeure()?->format('H:i:s'),
                        $imageName,
                        $id,
                    ]
                );

                $this->addFlash('success', 'Voyage modifié avec succès !');

                return $this->redirectToRoute('admin_voyage_index');
            }
        }

        return $this->render('admin/voyage/edit.html.twig', [
            'voyage'       => $voyage,
            'errors'       => $errors,
            'nom'          => $formData['nom'],
            'description'  => $formData['description'],
            'capacite'     => $formData['capacite'],
            'prix'         => $formData['prix'],
            'dateCreation' => $formData['dateCreation'],
            'heure'        => $formData['heure'],
        ]);
    }

    // =========================================================
    //  DELETE
    // =========================================================
   // =========================================================
//  DELETE
// =========================================================
// =========================================================
//  DELETE - Version sans CSRF (pour déboguer)
// =========================================================
// =========================================================
//  DELETE
// =========================================================
// =========================================================
//  DELETE - Version corrigée sans CSRF problem
// =========================================================
#[Route('/{id}/delete', name: 'admin_voyage_delete', methods: ['POST'])]
public function delete(Request $request, Connection $connection, int $id): Response
{
    // Récupérer le token depuis le formulaire
    $submittedToken = $request->request->get('_token');
    
    // Vérification plus flexible du token
    try {
        // Essayer de valider le token spécifique
        if (!$this->isCsrfTokenValid('delete_voyage_' . $id, $submittedToken)) {
            // Si échec, essayer avec le token générique
            if (!$this->isCsrfTokenValid('delete_voyage', $submittedToken)) {
                $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                return $this->redirectToRoute('admin_voyage_index');
            }
        }
    } catch (\Exception $e) {
        // En cas d'erreur CSRF, on continue quand même pour le débogage
        // À retirer en production
        $this->addFlash('warning', 'Attention: Vérification CSRF ignorée temporairement.');
    }
    
    try {
        // Vérifier si le voyage existe
        $voyage = $connection->fetchAssociative("SELECT image FROM voyages WHERE idV = ?", [$id]);
        
        if (!$voyage) {
            $this->addFlash('error', 'Voyage non trouvé');
            return $this->redirectToRoute('admin_voyage_index');
        }
        
        // Compter les programmes associés
        $programmeCount = $connection->fetchOne("SELECT COUNT(*) FROM programmes WHERE idV = ?", [$id]);
        
        if ($programmeCount > 0) {
            // Supprimer les réservations des programmes
            $connection->executeStatement("
                DELETE rp FROM reservationprog rp 
                INNER JOIN programmes p ON rp.idP = p.idProg 
                WHERE p.idV = ?
            ", [$id]);
            
            // Supprimer les programmes
            $connection->executeStatement("DELETE FROM programmes WHERE idV = ?", [$id]);
        }
        
        // Supprimer l'image du voyage
        if ($voyage['image'] && file_exists($this->getParameter('uploads_directory') . '/' . $voyage['image'])) {
            unlink($this->getParameter('uploads_directory') . '/' . $voyage['image']);
        }
        
        // Supprimer le voyage
        $deleted = $connection->executeStatement("DELETE FROM voyages WHERE idV = ?", [$id]);
        
        if ($deleted > 0) {
            $this->addFlash('success', "Voyage et ses $programmeCount programme(s) supprimés avec succès !");
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression du voyage');
        }
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('admin_voyage_index');
}
    // =========================================================
    //  PROGRAMMES
    // =========================================================
    #[Route('/{id}/programmes', name: 'admin_voyage_programmes')]
    public function programmes(Connection $connection, int $id): Response
    {
        $voyage     = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$id]);
        $programmes = $connection->fetchAllAssociative(
            "SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC", [$id]
        );

        return $this->render('admin/voyage/programmes.html.twig', [
            'voyage'     => $voyage,
            'programmes' => $programmes,
        ]);
    }

    // =========================================================
    //  GENERATE IMAGE (Pexels / Unsplash)
    // =========================================================
    #[Route('/generate-image', name: 'admin_voyage_generate_image', methods: ['POST'])]
    public function generateImage(Request $request): JsonResponse
    {
        $nom = trim($request->request->get('nom', ''));

        if (empty($nom)) {
            return $this->json(['success' => false, 'error' => 'Nom du voyage requis']);
        }

        $imageName = $this->getFreeImage($nom);

        if ($imageName) {
            return $this->json(['success' => true, 'image' => $imageName]);
        }

        return $this->json(['success' => false, 'error' => 'Impossible de générer une image']);
    }

    // =========================================================
    //  GENERATE DESCRIPTION (Ollama Llama3 – 100% local)
    // =========================================================
    #[Route('/generate-description', name: 'admin_voyage_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request, Connection $connection): JsonResponse
    {
        $nom = trim($request->request->get('nom', ''));

        if (empty($nom)) {
            return $this->json(['success' => false, 'error' => 'Nom du voyage requis']);
        }

        // Vérifie qu'Ollama est bien disponible
        if (!$this->ollamaService->isAvailable()) {
            return $this->json([
                'success' => false,
                'error'   => 'Ollama n\'est pas disponible. Lancez : ollama serve',
            ]);
        }

        // Récupère jusqu'à 3 descriptions existantes pour le few-shot
        $exemples = $connection->fetchAllAssociative(
            "SELECT nom, description FROM voyages
             WHERE description IS NOT NULL AND description != '' AND LENGTH(description) > 50
             ORDER BY RAND()
             LIMIT 3"
        );

        try {
            $description = $this->ollamaService->generateDescription($nom, $exemples);

            return $this->json([
                'success'     => true,
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error'   => 'Erreur Ollama : ' . $e->getMessage(),
            ]);
        }
    }

    // =========================================================
    //  HELPERS – images gratuites
    // =========================================================

    private function getFreeImage(string $query): ?string
    {
        $pexelsKey = $_ENV['PEXELS_API_KEY'] ?? '';

        if (!empty($pexelsKey)) {
            $image = $this->getPexelsImage($query, $pexelsKey);
            if ($image) {
                return $image;
            }
        }

        return $this->getUnsplashImage($query);
    }

    private function getPexelsImage(string $query, string $apiKey): ?string
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                'https://api.pexels.com/v1/search?query=' . urlencode($query . ' tunisia travel') . '&per_page=1',
                [
                    'headers' => ['Authorization' => $apiKey],
                    'timeout' => 15,
                ]
            );

            $data = $response->toArray();

            if (isset($data['photos'][0]['src']['large'])) {
                $imageContent = file_get_contents($data['photos'][0]['src']['large']);
                if ($imageContent) {
                    $imageName  = 'pexels_' . preg_replace('/[^a-zA-Z0-9]/', '_', $query) . '_' . uniqid() . '.jpg';
                    file_put_contents($this->getParameter('uploads_directory') . '/' . $imageName, $imageContent);

                    return $imageName;
                }
            }
        } catch (\Exception $e) {
            // fallback
        }

        return null;
    }

    private function getUnsplashImage(string $query): ?string
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                'https://source.unsplash.com/featured/800x600?' . urlencode($query . ' tunisia travel')
            );

            $imageContent = $response->getContent();

            if ($imageContent) {
                $imageName = 'unsplash_' . preg_replace('/[^a-zA-Z0-9]/', '_', $query) . '_' . uniqid() . '.jpg';
                file_put_contents($this->getParameter('uploads_directory') . '/' . $imageName, $imageContent);

                return $imageName;
            }
        } catch (\Exception $e) {
            // fallback final
        }

        return null;
    }

    // =========================================================
    //  IA ANALYZER - Dashboard intelligent
    // =========================================================
    
    #[Route('/analyze', name: 'admin_voyage_analyze', methods: ['GET'])]
    public function analyze(AIAnalyzerService $analyzer): JsonResponse
    {
        $result = $analyzer->analyzeAndGenerateTasks();
        
        return $this->json([
            'success' => true,
            'alerts' => $result['alerts'],
            'tasks' => $result['tasks'],
            'last_analysis' => $analyzer->getLastAnalysis()
        ]);
    }

    #[Route('/tasks', name: 'admin_voyage_tasks', methods: ['GET'])]
    public function getTasks(AIAnalyzerService $analyzer): JsonResponse
    {
        return $this->json([
            'tasks' => $analyzer->getTasks(),
            'alerts' => $analyzer->getAlerts(),
            'last_analysis' => $analyzer->getLastAnalysis()
        ]);
    }

    #[Route('/tasks/{taskId}/status', name: 'admin_voyage_task_status', methods: ['POST'])]
    public function updateTaskStatus(Request $request, AIAnalyzerService $analyzer, string $taskId): JsonResponse
    {
        $newStatus = $request->request->get('status', '');
        
        if (!in_array($newStatus, ['todo', 'doing', 'done'])) {
            return $this->json(['success' => false, 'error' => 'Statut invalide']);
        }
        
        $success = $analyzer->updateTaskStatus($taskId, $newStatus);
        
        return $this->json(['success' => $success]);
    }

    // =========================================================
    //  AI REPORT GENERATOR - Rapport hebdomadaire
    // =========================================================

    #[Route('/report/generate', name: 'admin_voyage_report_generate', methods: ['POST'])]
    public function generateReport(AIReportGenerator $reportGenerator): JsonResponse
    {
        try {
            $report = $reportGenerator->generateWeeklyReport();
            
            return $this->json([
                'success' => true,
                'report' => $report
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/report/pdf', name: 'admin_voyage_report_pdf', methods: ['POST'])]
    public function downloadPDFReport(Request $request, AIReportGenerator $reportGenerator): Response
    {
        try {
            // Récupérer le rapport depuis la requête ou le générer
            $reportData = json_decode($request->getContent(), true);
            
            if (isset($reportData['report'])) {
                $report = $reportData['report'];
            } else {
                $report = $reportGenerator->generateWeeklyReport();
            }
            
            $pdfContent = $reportGenerator->generatePDFReport($report);
            
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="rapport_hebdomadaire_' . date('Y-m-d') . '.pdf"');
            
            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
            return $this->redirectToRoute('admin_voyage_index');
        }
    }

    #[Route('/report/preview', name: 'admin_voyage_report_preview', methods: ['GET'])]
    public function previewReport(AIReportGenerator $reportGenerator): JsonResponse
    {
        try {
            $report = $reportGenerator->generateWeeklyReport();
            
            return $this->json([
                'success' => true,
                'report' => $report
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    // Dans AdminVoyageController.php, ajoutez cette méthode temporaire

#[Route('/report/test-data', name: 'admin_voyage_report_test', methods: ['GET'])]
public function testReportData(Connection $connection): JsonResponse
{
    try {
        // Test programmes
        $programmes = $connection->fetchAllAssociative("SELECT * FROM programmes");
        
        // Test réservations
        $reservations = $connection->fetchAllAssociative("SELECT * FROM reservationprog");
        
        // Test jointure
        $joined = $connection->fetchAllAssociative("
            SELECT p.*, r.* 
            FROM programmes p 
            LEFT JOIN reservationprog r ON p.idProg = r.idP 
            LIMIT 10
        ");
        
        return $this->json([
            'success' => true,
            'programmes_count' => count($programmes),
            'reservations_count' => count($reservations),
            'programmes_sample' => array_slice($programmes, 0, 3),
            'reservations_sample' => array_slice($reservations, 0, 3),
            'joined_sample' => $joined
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
#[Route('/report/debug', name: 'admin_voyage_report_debug', methods: ['GET'])]
public function debugReport(Connection $connection): JsonResponse
{
    try {
        // Test direct des voyages
        $voyages = $connection->fetchAllAssociative("SELECT * FROM voyages");
        
        // Test des réservations
        $reservations = $connection->fetchAllAssociative("
            SELECT rp.*, p.idV, v.nom as voyage_nom
            FROM reservationprog rp
            LEFT JOIN programmes p ON rp.idP = p.idProg
            LEFT JOIN voyages v ON p.idV = v.idV
        ");
        
        // Test des revenus
        $revenues = $connection->fetchAssociative("
            SELECT 
                SUM(rp.nbre * v.prix) as total,
                SUM(CASE WHEN rp.statutPaiement = 'payé' THEN rp.nbre * v.prix ELSE 0 END) as paid,
                SUM(CASE WHEN rp.statutPaiement = 'en_attente' THEN rp.nbre * v.prix ELSE 0 END) as pending
            FROM reservationprog rp
            LEFT JOIN programmes p ON rp.idP = p.idProg
            LEFT JOIN voyages v ON p.idV = v.idV
        ");
        
        return $this->json([
            'voyages_count' => count($voyages),
            'voyages' => $voyages,
            'reservations_count' => count($reservations),
            'reservations' => $reservations,
            'revenues' => $revenues
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()]);
    }
}
#[Route('/report/diagnostic', name: 'admin_voyage_diagnostic', methods: ['GET'])]
public function diagnostic(Connection $connection): JsonResponse
{
    try {
        // 1. Voir la structure de la table programmes
        $programmesColumns = $connection->fetchAllAssociative("SHOW COLUMNS FROM programmes");
        
        // 2. Voir tous les programmes
        $programmes = $connection->fetchAllAssociative("SELECT * FROM programmes");
        
        // 3. Voir toutes les réservations
        $reservations = $connection->fetchAllAssociative("SELECT * FROM reservationprog LIMIT 20");
        
        // 4. Tester la jointure
        $testJoin = $connection->fetchAllAssociative("
            SELECT 
                rp.idRP,
                rp.idP as reservation_idP,
                p.idProg as programme_idProg,
                p.nom as programme_nom,
                p.idV,
                v.nom as voyage_nom
            FROM reservationprog rp
            LEFT JOIN programmes p ON rp.idP = p.idProg
            LEFT JOIN voyages v ON p.idV = v.idV
            LIMIT 20
        ");
        
        return $this->json([
            'programmes_columns' => $programmesColumns,
            'programmes_count' => count($programmes),
            'programmes_sample' => array_slice($programmes, 0, 5),
            'reservations_count' => count($reservations),
            'reservations_sample' => array_slice($reservations, 0, 5),
            'test_join_results' => $testJoin,
            'problem_analysis' => $this->analyzeProblem($programmes, $reservations)
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()]);
    }
}

private function analyzeProblem($programmes, $reservations): array
{
    $analysis = [];
    
    // Vérifier les types de idProg
    if (!empty($programmes)) {
        $firstProg = $programmes[0];
        $analysis['programmes_idProg_type'] = gettype($firstProg['idProg']);
        $analysis['programmes_idProg_example'] = $firstProg['idProg'];
    }
    
    // Vérifier les types de idP dans réservations
    if (!empty($reservations)) {
        $firstRes = $reservations[0];
        $analysis['reservations_idP_type'] = gettype($firstRes['idP']);
        $analysis['reservations_idP_example'] = $firstRes['idP'];
    }
    
    // Vérifier si les valeurs correspondent
    $progIds = array_map(function($p) { return (string)$p['idProg']; }, $programmes);
    $resIds = array_map(function($r) { return (string)$r['idP']; }, $reservations);
    
    $analysis['programmes_ids'] = array_slice($progIds, 0, 10);
    $analysis['reservations_ids'] = array_slice($resIds, 0, 10);
    $analysis['matching_ids'] = array_intersect($progIds, $resIds);
    
    return $analysis;
}
    // =========================================================
    //  GENERATE IMAGE WITH OLLAMA AI
    // =========================================================

    #[Route('/generate-image-ai', name: 'admin_voyage_generate_image_ai', methods: ['POST'])]
    public function generateImageWithAI(Request $request): JsonResponse
    {
        $nom = trim($request->request->get('nom', ''));

        if (empty($nom)) {
            return $this->json(['success' => false, 'error' => 'Nom du voyage requis']);
        }

        if (!$this->ollamaService->isImageModelAvailable()) {
            return $this->json([
                'success' => false, 
                'error' => 'Modèle d\'images non disponible. Lancez: ollama pull llava'
            ]);
        }

        try {
            $keywords = $this->ollamaService->generateImageKeywords($nom);
            
            if ($keywords) {
                $searchQuery = $nom . ' ' . $keywords;
            } else {
                $searchQuery = $nom . ' tunisia travel landscape';
            }
            
            $pexelsKey = $_ENV['PEXELS_API_KEY'] ?? '';
            $imageName = null;
            
            if (!empty($pexelsKey)) {
                $imageName = $this->getPexelsImage($searchQuery, $pexelsKey);
            }
            
            if (!$imageName) {
                $imageName = $this->getUnsplashImage($searchQuery);
            }
            
            if ($imageName) {
                return $this->json([
                    'success' => true,
                    'image' => $imageName,
                    'keywords_used' => $keywords
                ]);
            }
            
            $fallbackImage = $this->getFreeImage($nom);
            if ($fallbackImage) {
                return $this->json([
                    'success' => true,
                    'image' => $fallbackImage,
                    'keywords_used' => 'recherche standard'
                ]);
            }
            
            return $this->json([
                'success' => false,
                'error' => 'Aucune image trouvée pour ce voyage'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
}