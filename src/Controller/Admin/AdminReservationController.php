<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\DiscordNotifierService;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/reservations')]
class AdminReservationController extends AbstractController
{
    const ITEMS_PER_PAGE = 10;
    
    #[Route('/', name: 'admin_reservation_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'idRP');
        $directionRaw = (string) $request->query->get('direction', 'DESC');
        
        $allowedSorts = ['idRP', 'nom', 'prenom', 'email', 'telephone', 'nbre', 'prixProg', 'dateProgramme', 'statutPaiement', 'programme_nom'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'idRP';
        }
        
        $direction = strtoupper($directionRaw) === 'ASC' ? 'ASC' : 'DESC';
        
        $searchCondition = "";
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = " WHERE (r.nom LIKE :search OR r.prenom LIKE :search OR r.email LIKE :search OR r.telephone LIKE :search OR p.nom LIKE :search) ";
            $params['search'] = "%$search%";
        }
        
        $countQuery = "
            SELECT COUNT(*) FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            $searchCondition
        ";
        $totalReservations = $connection->fetchOne($countQuery, $params);
        $totalPages = max(1, ceil($totalReservations / self::ITEMS_PER_PAGE));
        
        $orderByClause = "";
        if ($sort === 'programme_nom') {
            $orderByClause = " ORDER BY p.nom $direction";
        } else {
            $orderByClause = " ORDER BY r.$sort $direction";
        }
        
        $reservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom, v.nom as voyage_nom
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            LEFT JOIN voyages v ON p.idV = v.idV
            $searchCondition
            $orderByClause
            LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)(($page - 1) * self::ITEMS_PER_PAGE),
            $params
        );
        
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'total_count' => $totalReservations,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        $token = $request->request->get('_token');
        $tokenString = is_string($token) ? $token : null;

        if ($this->isCsrfTokenValid('delete_reservation_' . $id, $tokenString)) {
            $connection->executeStatement("DELETE FROM reservationprog WHERE idRP = ?", [$id]);
            $this->addFlash('success', 'Réservation supprimée avec succès !');
        }
        
        return $this->redirectToRoute('admin_reservation_index');
    }

    #[Route('/pdf', name: 'admin_reservation_pdf')]
    public function pdf(Connection $connection): Response
    {
        $reservations = $connection->fetchAllAssociative("
            SELECT r.*, p.nom as programme_nom, v.nom as voyage_nom
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            LEFT JOIN voyages v ON p.idV = v.idV
            ORDER BY r.dateProgramme DESC
        ");

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->renderView('admin/reservation/pdf.html.twig', [
            'reservations' => $reservations,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport_reservations_' . date('Y-m-d') . '.pdf"'
        ]);
    }
    
    // =========================================================
    //  DISCORD NOTIFICATIONS ROUTES
    // =========================================================

    #[Route('/discord/mark-read', name: 'admin_discord_mark_read', methods: ['POST'])]
    public function markDiscordNotificationsRead(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $notifications = $session->get('discord_notifications', []);
        
        foreach ($notifications as &$notif) {
            $notif['read'] = true;
        }
        
        $session->set('discord_notifications', $notifications);
        
        return $this->json(['success' => true]);
    }

    #[Route('/discord/test', name: 'admin_discord_test', methods: ['GET'])]
    public function testDiscord(DiscordNotifierService $discordNotifier): Response
    {
        $result = $discordNotifier->testConnection();
        
        if ($result) {
            $this->addFlash('success', '✅ Notification Discord envoyée avec succès ! Vérifiez votre salon Discord.');
        } else {
            $this->addFlash('error', '❌ Erreur d\'envoi Discord. Vérifiez la configuration du webhook.');
        }
        
        return $this->redirectToRoute('admin_voyage_index');
    }
    
    #[Route('/discord/force-check', name: 'admin_discord_force_check', methods: ['GET'])]
    public function forceCheckDiscord(Request $request, Connection $connection, DiscordNotifierService $discordNotifier): Response
    {
        $lastReservation = $connection->fetchAssociative("
            SELECT r.*, p.nom as programme_nom, v.nom as voyage_nom, v.idV as voyage_id
            FROM reservationprog r 
            LEFT JOIN programmes p ON r.idP = p.idProg 
            LEFT JOIN voyages v ON p.idV = v.idV
            ORDER BY r.idRP DESC
            LIMIT 1
        ");
        
        if (!$lastReservation) {
            $this->addFlash('warning', 'Aucune réservation trouvée dans la base.');
            return $this->redirectToRoute('admin_voyage_index');
        }

        // FIX :198 — notifyNewReservation() expects voyage_id as int (never null).
        // The LEFT JOIN may yield NULL when no voyage row matches, so we cast with
        // a fallback of 0 instead of null to satisfy the strict array shape.
        $typedReservation = [
            'idRP'          => (int) ($lastReservation['idRP'] ?? 0),
            'nom'           => (string) ($lastReservation['nom'] ?? ''),
            'prenom'        => (string) ($lastReservation['prenom'] ?? ''),
            'telephone'     => (string) ($lastReservation['telephone'] ?? ''),
            'email'         => (string) ($lastReservation['email'] ?? ''),
            'nbre'          => (int) ($lastReservation['nbre'] ?? 0),
            'dateProgramme' => (string) ($lastReservation['dateProgramme'] ?? ''),
            'programme_nom' => (string) ($lastReservation['programme_nom'] ?? ''),
            'voyage_nom'    => (string) ($lastReservation['voyage_nom'] ?? ''),
            'voyage_id'     => (int) ($lastReservation['voyage_id'] ?? 0),
        ];

        if (isset($lastReservation['prixProg']) && is_numeric($lastReservation['prixProg'])) {
            $typedReservation['prixProg'] = (float) $lastReservation['prixProg'];
        }

        $sent = $discordNotifier->notifyNewReservation($typedReservation);
        
        if ($sent) {
            $this->addFlash('success', '✅ Notification envoyée pour la réservation #' . $typedReservation['idRP']);
            
            $session = $request->getSession();
            $notifications = $session->get('discord_notifications', []);
            array_unshift($notifications, [
                'id'      => $typedReservation['idRP'],
                'message' => "🆕 Réservation #{$typedReservation['idRP']} - {$typedReservation['prenom']} {$typedReservation['nom']}",
                'time'    => date('H:i:s'),
                'read'    => false,
            ]);
            $session->set('discord_notifications', $notifications);
            $session->set('last_notified_reservation_id', $typedReservation['idRP']);
            
        } else {
            $this->addFlash('error', '❌ Erreur lors de l\'envoi Discord');
        }
        
        return $this->redirectToRoute('admin_voyage_index');
    }

    #[Route('/discord/diagnostic', name: 'admin_discord_diagnostic', methods: ['GET'])]
    public function discordDiagnostic(DiscordNotifierService $discordNotifier): Response
    {
        $envUrl = $_ENV['DISCORD_WEBHOOK_URL'] ?? '';

        $payload = json_encode(['content' => '🔍 Test diagnostic Symfony - ' . date('H:i:s')]);

        $httpCode = 0;
        if ($envUrl !== '' && $payload !== false) {
            $ch = curl_init($envUrl);
            if ($ch !== false) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }
        }

        $displayUrl = $envUrl !== '' ? substr($envUrl, 0, 80) . '...' : 'NON TROUVEE';
        $output  = "<h1>🔧 Diagnostic Discord</h1>";
        $output .= "<p>📌 Variable .env: <code>" . htmlspecialchars($displayUrl) . "</code></p>";
        $output .= "<p>📡 Test cURL: Code HTTP <strong>" . $httpCode . "</strong></p>";

        if ($httpCode === 204) {
            $output .= "<p style='color:green'>✅ Webhook fonctionne ! Regarde Discord.</p>";
        } else {
            $output .= "<p style='color:red'>❌ Webhook ne répond pas correctement.</p>";
        }

        $testResult = $discordNotifier->testConnection();
        $output .= "<p>📨 Service Symfony: " . ($testResult ? "<span style='color:green'>✅ OK</span>" : "<span style='color:red'>❌ ÉCHEC</span>") . "</p>";

        return new Response($output);
    }
}