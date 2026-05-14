<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

class AIReportGenerator
{
    private Connection $connection;
    private OllamaService $ollamaService;
    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        OllamaService $ollamaService,
        LoggerInterface $logger
    ) {
        $this->connection    = $connection;
        $this->ollamaService = $ollamaService;
        $this->logger        = $logger;
    }

<<<<<<< HEAD
    /**
     * @return array{
     *     generated_at: string,
     *     period: array{start: string, end: string},
     *     summary: array{
     *         total_voyages: int,
     *         total_reservations: int,
     *         total_participants: int,
     *         total_revenue: float,
     *         paid_revenue: float,
     *         pending_revenue: float,
     *         avg_occupancy_rate: float
     *     },
     *     best_voyages: array<int, array<string, mixed>>,
     *     worst_voyages: array<int, array<string, mixed>>,
     *     ai_insights: string,
     *     detailed_voyages: array<int, array<string, mixed>>
     * }
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function generateWeeklyReport(): array
    {
        try {
            $startDate = new \DateTime('-7 days');
            $endDate   = new \DateTime();

            $voyagesData  = $this->getVoyagesPerformance();
            $revenues     = $this->calculateTotalRevenues();
            $bestVoyages  = $this->getBestVoyages($voyagesData);
            $worstVoyages = $this->getWorstVoyages($voyagesData);

            // ─── 100% Ollama pour les insights ───────────────────────────
            $aiInsights = $this->generateAIInsightsViaOllama($voyagesData, $revenues, $bestVoyages, $worstVoyages);

            $report = [
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'period'       => [
                    'start' => $startDate->format('Y-m-d'),
                    'end'   => $endDate->format('Y-m-d'),
                ],
                'summary' => [
<<<<<<< HEAD
                    // Suppression des ?? car les offsets existent toujours
                    'total_voyages'      => $voyagesData['total_voyages'],
                    'total_reservations' => $voyagesData['total_reservations'],
                    'total_participants' => $voyagesData['total_participants'],
                    'total_revenue'      => $revenues['total_revenue'],
                    'paid_revenue'       => $revenues['paid_revenue'],
                    'pending_revenue'    => $revenues['pending_revenue'],
                    'avg_occupancy_rate' => $voyagesData['avg_occupancy_rate'],
=======
                    'total_voyages'      => $voyagesData['total_voyages']      ?? 0,
                    'total_reservations' => $voyagesData['total_reservations'] ?? 0,
                    'total_participants' => $voyagesData['total_participants'] ?? 0,
                    'total_revenue'      => $revenues['total_revenue']         ?? 0,
                    'paid_revenue'       => $revenues['paid_revenue']          ?? 0,
                    'pending_revenue'    => $revenues['pending_revenue']       ?? 0,
                    'avg_occupancy_rate' => $voyagesData['avg_occupancy_rate'] ?? 0,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                ],
                'best_voyages'     => $bestVoyages,
                'worst_voyages'    => $worstVoyages,
                'ai_insights'      => $aiInsights,
<<<<<<< HEAD
                'detailed_voyages' => $voyagesData['voyages'],
=======
                'detailed_voyages' => $voyagesData['voyages'] ?? [],
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            ];

            return $report;

        } catch (\Exception $e) {
            $this->logger->error('Erreur génération rapport: ' . $e->getMessage());
            return $this->getDefaultReport();
        }
    }

    /**
     * Génère les insights via Ollama (100% IA locale, pas de texte hardcodé).
<<<<<<< HEAD
     *
     * @param array<string, mixed> $voyagesData
     * @param array<string, float> $revenues
     * @param array<int, array<string, mixed>> $bestVoyages
     * @param array<int, array<string, mixed>> $worstVoyages
     * @return string
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    private function generateAIInsightsViaOllama(
        array $voyagesData,
        array $revenues,
        array $bestVoyages,
        array $worstVoyages
    ): string {

        // Préparer les données pour Ollama
        $reportDataForAI = [
            'periode'             => 'Cette semaine',
            'total_voyages'       => $voyagesData['total_voyages'] ?? 0,
            'total_reservations'  => $voyagesData['total_reservations'] ?? 0,
            'total_participants'  => $voyagesData['total_participants'] ?? 0,
            'revenu_total'        => ($revenues['total_revenue'] ?? 0) . ' DT',
            'revenu_paye'         => ($revenues['paid_revenue'] ?? 0) . ' DT',
            'revenu_en_attente'   => ($revenues['pending_revenue'] ?? 0) . ' DT',
            'taux_occupation_moy' => ($voyagesData['avg_occupancy_rate'] ?? 0) . '%',
            'meilleurs_voyages'   => array_map(function ($v) {
                return [
                    'nom'          => $v['nom'],
                    'participants' => $v['total_participants'],
                    'revenu_paye'  => $v['paid_revenue'] . ' DT',
                    'occupation'   => $v['occupancy_rate'] . '%',
                ];
            }, array_slice($bestVoyages, 0, 5)),
            'voyages_a_ameliorer' => array_map(function ($v) {
                return [
                    'nom'          => $v['nom'],
                    'participants' => $v['total_participants'],
                    'occupation'   => $v['occupancy_rate'] . '%',
                ];
            }, array_slice($worstVoyages, 0, 5)),
            'voyages_sans_reservation' => count(array_filter(
                $voyagesData['voyages'] ?? [],
                fn($v) => ($v['total_reservations'] ?? 0) == 0
            )),
        ];

        // Appel Ollama
<<<<<<< HEAD
        $aiText = $this->ollamaService->generate($reportDataForAI);
=======
        $aiText = $this->ollamaService->generateFinancialReport($reportDataForAI);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

        if (!empty($aiText)) {
            return $aiText;
        }

        // Fallback si Ollama indisponible
        return $this->generateStaticInsights($voyagesData, $revenues, $bestVoyages, $worstVoyages);
    }

    /**
     * Fallback statique si Ollama est indisponible.
<<<<<<< HEAD
     *
     * @param array<string, mixed> $voyagesData
     * @param array<string, float> $revenues
     * @param array<int, array<string, mixed>> $bestVoyages
     * @param array<int, array<string, mixed>> $worstVoyages
     * @return string
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
     */
    private function generateStaticInsights(
        array $voyagesData,
        array $revenues,
        array $bestVoyages,
        array $worstVoyages
    ): string {
        $summary  = "📊 RÉSUMÉ EXÉCUTIF\n";
        $summary .= sprintf(
            "%d voyages actifs avec %d réservations pour %d participants. Revenus totaux : %.2f DT (payés : %.2f DT, en attente : %.2f DT). Taux d'occupation moyen : %.1f%%.\n\n",
            $voyagesData['total_voyages'] ?? 0,
            $voyagesData['total_reservations'] ?? 0,
            $voyagesData['total_participants'] ?? 0,
            $revenues['total_revenue'] ?? 0,
            $revenues['paid_revenue'] ?? 0,
            $revenues['pending_revenue'] ?? 0,
            $voyagesData['avg_occupancy_rate'] ?? 0
        );

        $summary .= "✅ POINTS FORTS\n";
        if (!empty($bestVoyages)) {
            foreach (array_slice($bestVoyages, 0, 3) as $v) {
                $summary .= sprintf("• %s : %d participants, %.2f DT\n", $v['nom'], $v['total_participants'], $v['paid_revenue']);
            }
        } else {
            $summary .= "• Aucun voyage n'a généré de revenus cette semaine\n";
        }
        $summary .= "\n";

        $summary .= "⚠️ POINTS À AMÉLIORER\n";
        foreach (array_slice($worstVoyages, 0, 3) as $v) {
            $summary .= sprintf("• %s : seulement %d participants (%.1f%% d'occupation)\n", $v['nom'], $v['total_participants'], $v['occupancy_rate']);
        }

        $voyagesSansResa = count(array_filter($voyagesData['voyages'] ?? [], fn($v) => ($v['total_reservations'] ?? 0) == 0));
        if ($voyagesSansResa > 0) {
            $summary .= sprintf("• %d voyage(s) sans aucune réservation\n", $voyagesSansResa);
        }

        $summary .= "\n💡 RECOMMANDATIONS\n";
        if ($voyagesSansResa > 0) {
            $summary .= "• Lancer des offres promotionnelles sur les voyages sans réservation\n";
        }
        if (($revenues['pending_revenue'] ?? 0) > 0) {
            $summary .= "• Relancer les clients avec des paiements en attente\n";
        }
        $summary .= "• Proposer des offres early bird pour stimuler les réservations\n";

        return $summary;
    }

<<<<<<< HEAD
    /**
     * @return array{
     *     voyages: array<int, array<string, mixed>>,
     *     total_voyages: int,
     *     total_reservations: int,
     *     total_participants: int,
     *     avg_occupancy_rate: float
     * }
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function getVoyagesPerformance(): array
    {
        try {
            $voyagesWithStats = $this->connection->fetchAllAssociative("
                SELECT 
                    v.idV,
                    v.nom,
                    v.description,
                    v.capacite,
                    v.prix,
                    v.dateCreation,
                    v.image,
                    COUNT(DISTINCT rp.idRP) as total_reservations,
                    COALESCE(SUM(rp.nbre), 0) as total_participants,
                    COALESCE(SUM(CASE WHEN LOWER(rp.statutPaiement) = 'payé' THEN rp.nbre * COALESCE(rp.prixProg, v.prix) ELSE 0 END), 0) as paid_revenue,
                    COALESCE(SUM(CASE WHEN LOWER(rp.statutPaiement) != 'payé' THEN rp.nbre * COALESCE(rp.prixProg, v.prix) ELSE 0 END), 0) as pending_revenue
                FROM voyages v
                LEFT JOIN programmes p ON v.idV = p.idV
                LEFT JOIN reservationprog rp ON p.idProg = rp.idP
                GROUP BY v.idV
                ORDER BY v.idV DESC
            ");

            $totalVoyages            = count($voyagesWithStats);
            $totalReservationsGlobal = 0;
            $totalParticipantsGlobal = 0;
            $totalCapacityGlobal     = 0;
            $totalOccupiedGlobal     = 0;

            foreach ($voyagesWithStats as &$voyage) {
                $capacite = (int)($voyage['capacite'] ?? 50);
                $voyage['capacity']       = $capacite;
                $voyage['occupancy_rate'] = $capacite > 0 ? round(($voyage['total_participants'] / $capacite) * 100, 1) : 0;

                $totalReservationsGlobal += (int)$voyage['total_reservations'];
                $totalParticipantsGlobal += (int)$voyage['total_participants'];
                $totalCapacityGlobal     += $capacite;
                $totalOccupiedGlobal     += min((int)$voyage['total_participants'], $capacite);
            }

            $avgOccupancyRate = $totalCapacityGlobal > 0
                ? round(($totalOccupiedGlobal / $totalCapacityGlobal) * 100, 1)
                : 0;

            return [
                'voyages'            => $voyagesWithStats,
                'total_voyages'      => $totalVoyages,
                'total_reservations' => $totalReservationsGlobal,
                'total_participants' => $totalParticipantsGlobal,
                'avg_occupancy_rate' => $avgOccupancyRate,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur getVoyagesPerformance: ' . $e->getMessage());
            return ['voyages' => [], 'total_voyages' => 0, 'total_reservations' => 0, 'total_participants' => 0, 'avg_occupancy_rate' => 0];
        }
    }

<<<<<<< HEAD
    /**
     * @return array{total_revenue: float, paid_revenue: float, pending_revenue: float}
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function calculateTotalRevenues(): array
    {
        try {
            $revenues = $this->connection->fetchAssociative("
                SELECT 
                    COALESCE(SUM(rp.nbre * COALESCE(rp.prixProg, v.prix)), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN LOWER(rp.statutPaiement) = 'payé' THEN rp.nbre * COALESCE(rp.prixProg, v.prix) ELSE 0 END), 0) as paid_revenue,
                    COALESCE(SUM(CASE WHEN LOWER(rp.statutPaiement) != 'payé' THEN rp.nbre * COALESCE(rp.prixProg, v.prix) ELSE 0 END), 0) as pending_revenue
                FROM reservationprog rp
                INNER JOIN programmes p ON rp.idP = p.idProg
                INNER JOIN voyages v ON p.idV = v.idV
            ");

            return [
<<<<<<< HEAD
                'total_revenue'   => (float)($revenues['total_revenue'] ?? 0),
                'paid_revenue'    => (float)($revenues['paid_revenue'] ?? 0),
=======
                'total_revenue'   => (float)($revenues['total_revenue']   ?? 0),
                'paid_revenue'    => (float)($revenues['paid_revenue']    ?? 0),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                'pending_revenue' => (float)($revenues['pending_revenue'] ?? 0),
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur calculateTotalRevenues: ' . $e->getMessage());
            return ['total_revenue' => 0, 'paid_revenue' => 0, 'pending_revenue' => 0];
        }
    }

<<<<<<< HEAD
    /**
     * @param array<string, mixed> $voyagesData
     * @return array<int, array<string, mixed>>
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function getBestVoyages(array $voyagesData): array
    {
        $voyages = array_filter($voyagesData['voyages'] ?? [], fn($v) => ($v['paid_revenue'] ?? 0) > 0 || ($v['total_participants'] ?? 0) > 0);
        usort($voyages, fn($a, $b) => ($b['paid_revenue'] ?? 0) <=> ($a['paid_revenue'] ?? 0));
        return array_slice($voyages, 0, 5);
    }

<<<<<<< HEAD
    /**
     * @param array<string, mixed> $voyagesData
     * @return array<int, array<string, mixed>>
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function getWorstVoyages(array $voyagesData): array
    {
        $voyages = array_filter($voyagesData['voyages'] ?? [], fn($v) => ($v['total_reservations'] ?? 0) > 0);
        usort($voyages, fn($a, $b) => ($a['occupancy_rate'] ?? 0) <=> ($b['occupancy_rate'] ?? 0));
        return array_slice($voyages, 0, 5);
    }

<<<<<<< HEAD
    /**
     * @return array{
     *     generated_at: string,
     *     period: array{start: string, end: string},
     *     summary: array{
     *         total_voyages: int,
     *         total_reservations: int,
     *         total_participants: int,
     *         total_revenue: int,
     *         paid_revenue: int,
     *         pending_revenue: int,
     *         avg_occupancy_rate: int
     *     },
     *     best_voyages: array<int, mixed>,
     *     worst_voyages: array<int, mixed>,
     *     ai_insights: string,
     *     detailed_voyages: array<int, mixed>
     * }
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function getDefaultReport(): array
    {
        $now = new \DateTime();
        return [
            'generated_at'     => $now->format('Y-m-d H:i:s'),
            'period'           => ['start' => (new \DateTime('-7 days'))->format('Y-m-d'), 'end' => $now->format('Y-m-d')],
            'summary'          => ['total_voyages' => 0, 'total_reservations' => 0, 'total_participants' => 0, 'total_revenue' => 0, 'paid_revenue' => 0, 'pending_revenue' => 0, 'avg_occupancy_rate' => 0],
            'best_voyages'     => [],
            'worst_voyages'    => [],
            'ai_insights'      => "Aucune donnée disponible pour le moment.",
            'detailed_voyages' => [],
        ];
    }

<<<<<<< HEAD
    /**
     * @param array<string, mixed> $report
     * @return string
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function generatePDFReport(array $report): string
    {
        try {
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new Dompdf($options);
            $html   = $this->renderReportHTML($report);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();

        } catch (\Exception $e) {
            $this->logger->error('Erreur génération PDF: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

<<<<<<< HEAD
    /**
     * @param array<string, mixed> $report
     * @return string
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function renderReportHTML(array $report): string
    {
        $bestVoyages = $report['best_voyages'] ?? [];

        $html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Rapport Hebdomadaire</title>
<style>
@page { margin: 15px; }
body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #1f2937; background: #ffffff; margin: 0; padding: 0; }
.container { width: 100%; padding: 15px 10px 15px 15px; text-align: left; }
.header { text-align: left; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 18px; }
.header h1 { font-size: 18px; margin: 0; color: #111827; }
.header p { margin: 3px 0; color: #6b7280; font-size: 10px; }
.section-title { font-size: 12px; font-weight: bold; margin: 15px 0 8px; padding-left: 6px; border-left: 3px solid #ef4444; }
.stats { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
.stats td { width: 25%; text-align: center; padding: 8px; border: 1px solid #e5e7eb; }
.stats .value { font-size: 15px; font-weight: bold; color: #111827; }
.stats .label { font-size: 10px; color: #6b7280; }
.revenue-box { width: 100%; margin-top: 10px; border: 1px solid #e5e7eb; padding: 8px; }
.revenue-item { display: inline-block; width: 32%; text-align: center; }
.revenue-value { font-size: 13px; font-weight: bold; color: #111827; }
.revenue-label { font-size: 10px; color: #6b7280; }
.table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.table th { background: #111827; color: white; padding: 7px; font-size: 10px; text-align: left; }
.table td { border-bottom: 1px solid #e5e7eb; padding: 7px; font-size: 10px; }
.table tr:nth-child(even) { background: #f9fafb; }
.insights { margin-top: 12px; padding: 10px; border-left: 3px solid #ef4444; background: #f9fafb; font-size: 10px; white-space: pre-line; }
.footer { margin-top: 20px; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
<div class="container">
<div class="header">
    <h1>Rapport Hebdomadaire</h1>
    <p>TunisiaJourney - Analyse IA des performances</p>
    <p>Période: ' . ($report['period']['start'] ?? '-') . ' → ' . ($report['period']['end'] ?? '-') . '</p>
    <p>Généré le : ' . ($report['generated_at'] ?? date('Y-m-d H:i:s')) . ' | Propulsé par Ollama AI</p>
</div>
<table class="stats">
<tr>
<<<<<<< HEAD
    <td><div class="value">' . ($report['summary']['total_voyages'] ?? 0) . '</div><div class="label">Voyages</div></td>
    <td><div class="value">' . ($report['summary']['total_reservations'] ?? 0) . '</div><div class="label">Réservations</div></td>
    <td><div class="value">' . ($report['summary']['total_participants'] ?? 0) . '</div><div class="label">Participants</div></td>
    <td><div class="value">' . ($report['summary']['avg_occupancy_rate'] ?? 0) . '%</div><div class="label">Occupation</div></td>
=======
<td><div class="value">' . ($report['summary']['total_voyages'] ?? 0) . '</div><div class="label">Voyages</div></td>
<td><div class="value">' . ($report['summary']['total_reservations'] ?? 0) . '</div><div class="label">Réservations</div></td>
<td><div class="value">' . ($report['summary']['total_participants'] ?? 0) . '</div><div class="label">Participants</div></td>
<td><div class="value">' . ($report['summary']['avg_occupancy_rate'] ?? 0) . '%</div><div class="label">Occupation</div></td>
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
</tr>
</table>
<div class="revenue-box">
<div class="revenue-item"><div class="revenue-value">' . number_format($report['summary']['total_revenue'] ?? 0, 2) . ' DT</div><div class="revenue-label">Total</div></div>
<div class="revenue-item"><div class="revenue-value">' . number_format($report['summary']['paid_revenue'] ?? 0, 2) . ' DT</div><div class="revenue-label">Payé</div></div>
<div class="revenue-item"><div class="revenue-value">' . number_format($report['summary']['pending_revenue'] ?? 0, 2) . ' DT</div><div class="revenue-label">En attente</div></div>
</div>
<div class="section-title">Meilleurs voyages</div>
<table class="table">
<tr><th>Voyage</th><th>Participants</th><th>Occupation</th><th>Revenus</th></tr>';

        foreach ($bestVoyages as $v) {
            $html .= '<tr>
<<<<<<< HEAD
    <td>' . htmlspecialchars($v['nom'] ?? '') . '</td>
    <td>' . ($v['total_participants'] ?? 0) . '</td>
    <td>' . ($v['occupancy_rate'] ?? 0) . '%</td>
    <td>' . number_format($v['paid_revenue'] ?? 0, 2) . ' DT</td>
</tr>';
        }

        $html .= '<tr>
=======
<td>' . htmlspecialchars($v['nom'] ?? '') . '</td>
<td>' . ($v['total_participants'] ?? 0) . '</td>
<td>' . ($v['occupancy_rate'] ?? 0) . '%</td>
<td>' . number_format($v['paid_revenue'] ?? 0, 2) . ' DT</td>
</tr>';
        }

        $html .= '</table>
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
<div class="section-title">Insights IA (Ollama)</div>
<div class="insights">' . nl2br(htmlspecialchars($report['ai_insights'] ?? 'Aucun insight disponible')) . '</div>
<div class="footer">Rapport généré automatiquement par Ollama AI — TunisiaJourney</div>
</div>
</body>
</html>';

        return $html;
    }
}