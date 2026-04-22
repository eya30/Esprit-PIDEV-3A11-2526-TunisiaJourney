<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class AIAnalyzerService
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

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function analyzeAndGenerateTasks(): array
    {
        $tasks  = [];
        $alerts = [];

        // ─── Récupérer tous les programmes actifs ────────────────────────
        $programmes = $this->connection->fetchAllAssociative("
            SELECT p.*, v.nom as voyage_nom, v.capacite as voyage_capacite
            FROM programmes p
            LEFT JOIN voyages v ON p.idV = v.idV
            WHERE p.dateFin >= CURDATE() OR p.dateFin IS NULL
            ORDER BY p.dateDebut ASC
        ");

        // ─── Données pour Ollama (contexte global) ───────────────────────
        $ollamaContextData = [];

        foreach ($programmes as $programme) {

            // ─── Stats réservations ──────────────────────────────────────
            $reservations = $this->connection->fetchAllAssociative("
                SELECT COUNT(*) as total_reservations,
                       COALESCE(SUM(nbre), 0) as total_personnes,
                       COUNT(CASE WHEN statutPaiement = 'payé'       THEN 1 END) as payes,
                       COUNT(CASE WHEN statutPaiement = 'en_attente' THEN 1 END) as en_attente
                FROM reservationprog
                WHERE idP = ?
            ", [$programme['idProg']]);

            $stats       = $reservations[0] ?? ['total_reservations' => 0, 'total_personnes' => 0, 'payes' => 0, 'en_attente' => 0];
            $capaciteMax = $programme['capacite_max'] ?? $programme['voyage_capacite'] ?? 50;
            $placesDisponibles = $capaciteMax - ($stats['total_personnes'] ?? 0);

            // ─── Calcul jours restants avant départ ─────────────────────
            $joursRestants = 0;
            if ($programme['dateDebut']) {
                $dateDebut = new \DateTime($programme['dateDebut']);
                $now       = new \DateTime();
                $diff      = $now->diff($dateDebut);
                $joursRestants = ($dateDebut > $now) ? $diff->days : 0;
            }

            $prixActuel = (float)($programme['prix'] ?? 0);
            $taux = $capaciteMax > 0 ? round(($stats['total_personnes'] / $capaciteMax) * 100, 1) : 0;

            // Accumule les données pour Ollama
            $ollamaContextData[] = [
                'programme'          => $programme['nom'],
                'voyage'             => $programme['voyage_nom'],
                'jours_avant_depart' => $joursRestants,
                'capacite'           => $capaciteMax,
                'reservations'       => (int)$stats['total_reservations'],
                'personnes'          => (int)$stats['total_personnes'],
                'places_libres'      => $placesDisponibles,
                'taux_occupation'    => $taux . '%',
                'paiements_en_attente' => (int)$stats['en_attente'],
                'prix_actuel'        => $prixActuel . ' DT',
            ];

            // ═══════════════════════════════════════════════════════════════
            // ALERTE 1 : AUCUNE RÉSERVATION (critique)
            // ═══════════════════════════════════════════════════════════════
            if ($stats['total_reservations'] == 0 && $joursRestants > 0) {

                $prixFlash  = $prixActuel * 0.70;
                $prixEarlyb = $prixActuel * 0.85;
                $urgenceLabel = $joursRestants < 14 ? '🔴 URGENT' : ($joursRestants < 30 ? '🟠 PRIORITAIRE' : '🟡 À surveiller');

                $idees = $this->buildNoReservationIdeas($programme['nom'], $prixActuel, $prixFlash, $prixEarlyb, $joursRestants);

                $alerts[] = [
                    'type'          => 'danger',
                    'programme_id'  => $programme['idProg'],
                    'programme_nom' => $programme['nom'],
                    'message'       => "🚨 {$urgenceLabel} — AUCUNE RÉSERVATION : \"{$programme['nom']}\" · {$joursRestants} jours avant départ",
                    'action'        => "Offre flash recommandée : " . number_format($prixFlash, 2) . " DT (au lieu de {$prixActuel} DT) — voir idées ci-dessous",
                    'idees'         => $idees,
                    'priority'      => 1,
                    'ai_insight'    => '',
                ];

                $tasks[] = $this->createTask(
                    "🚨 Offre spéciale pour \"{$programme['nom']}\" — Zéro inscription",
                    "Aucune réservation à {$joursRestants} jours du départ. Lancer offre flash -30% à " . number_format($prixFlash, 2) . " DT.",
                    'urgent',
                    'programme',
                    $programme['idProg']
                );
            }

            // ═══════════════════════════════════════════════════════════════
            // ALERTE 2 : TRÈS PEU DE RÉSERVATIONS (< 3 personnes)
            // ═══════════════════════════════════════════════════════════════
            elseif ($stats['total_personnes'] < 3 && $stats['total_personnes'] > 0
                    && $joursRestants > 0 && $joursRestants < 45) {

                $prixPromo = $prixActuel * 0.85;
                $manquants = 3 - $stats['total_personnes'];

                $alerts[] = [
                    'type'          => 'warning',
                    'programme_id'  => $programme['idProg'],
                    'programme_nom' => $programme['nom'],
                    'message'       => "⚠️ TRÈS PEU DE RÉSERVATIONS : {$stats['total_personnes']} personne(s) pour \"{$programme['nom']}\" — manque {$manquants} pour le minimum",
                    'action'        => "Lancer une promotion à " . number_format($prixPromo, 2) . " DT + offre \"Amenez un ami\"",
                    'idees'         => [
                        "🎁 Offre duo : -10% supplémentaires si inscription en groupe",
                        "📣 Boost payant sur Facebook & Instagram ciblé Tunisie",
                        "🤝 Partenariat avec agences locales pour co-promotion",
                        "📧 Email marketing sur base clients existants",
                        "⏰ Compte à rebours \"Offre valable 48h\" sur le site",
                    ],
                    'priority'      => 2,
                    'ai_insight'    => '',
                ];

                $tasks[] = $this->createTask(
                    "Augmenter visibilité de \"{$programme['nom']}\"",
                    "Seulement {$stats['total_personnes']} personne(s). Besoin de {$manquants} de plus. Promotion -15% à " . number_format($prixPromo, 2) . " DT + campagne duo.",
                    'high',
                    'programme',
                    $programme['idProg']
                );
            }

            // ═══════════════════════════════════════════════════════════════
            // ALERTE 3 : SURCHARGE — capacité voyage < nbre réservations
            // ═══════════════════════════════════════════════════════════════
            elseif ($placesDisponibles < 0) {

                $excedent = abs($placesDisponibles);

                $alerts[] = [
                    'type'          => 'danger',
                    'programme_id'  => $programme['idProg'],
                    'programme_nom' => $programme['nom'],
                    'message'       => "🔴 SURCHARGE CRITIQUE : \"{$programme['nom']}\" — {$stats['total_personnes']} personnes inscrites pour seulement {$capaciteMax} places ({$excedent} en trop !)",
                    'action'        => "URGENT : Augmenter la capacité de {$excedent} places OU contacter les derniers inscrits pour report",
                    'idees'         => [
                        "🚌 Ajouter un bus/véhicule supplémentaire si logistiquement possible",
                        "📞 Appeler les {$excedent} derniers inscrits pour proposer un report avec geste commercial",
                        "🗓️ Ouvrir une 2ème session identique rapidement",
                        "💰 Proposer un remboursement + bon d'achat de 20% aux volontaires",
                        "⚙️ Mettre à jour la capacité dans la base de données",
                    ],
                    'priority'      => 1,
                    'ai_insight'    => '',
                ];

                $tasks[] = $this->createTask(
                    "🔴 URGENT — Résoudre surcharge \"{$programme['nom']}\"",
                    "Programme complet et surchargé : {$stats['total_personnes']}/{$capaciteMax} ({$excedent} personnes en excédent). Capacité voyage insuffisante.",
                    'urgent',
                    'programme',
                    $programme['idProg']
                );
            }

            // ═══════════════════════════════════════════════════════════════
            // ALERTE 4 : PRESQUE COMPLET (≥ 80 %)
            // ═══════════════════════════════════════════════════════════════
            elseif ($placesDisponibles > 0 && $capaciteMax > 0
                    && ($stats['total_personnes'] / $capaciteMax) >= 0.8) {

                $pourcentage = round(($stats['total_personnes'] / $capaciteMax) * 100);

                $alerts[] = [
                    'type'          => 'warning',
                    'programme_id'  => $programme['idProg'],
                    'programme_nom' => $programme['nom'],
                    'message'       => "📊 PRESQUE COMPLET : \"{$programme['nom']}\" à {$pourcentage}% ({$placesDisponibles} places restantes)",
                    'action'        => "Afficher badge \"Plus que {$placesDisponibles} places !\" + préparer une 2ème session",
                    'idees'         => [
                        "🔖 Badge \"Dernières places\" sur la page programme",
                        "🚀 Ouvrir les inscriptions pour une 2ème session identique",
                        "📲 Story Instagram \"Plus que {$placesDisponibles} places !\"",
                        "💌 Email liste d'attente pour les intéressés",
                    ],
                    'priority'      => 2,
                    'ai_insight'    => '',
                ];

                $tasks[] = $this->createTask(
                    "Nouvelle session pour \"{$programme['nom']}\"",
                    "À {$pourcentage}% de capacité. Préparer une 2ème session et afficher l'urgence sur le site.",
                    'medium',
                    'programme',
                    $programme['idProg']
                );
            }

            // ═══════════════════════════════════════════════════════════════
            // ALERTE 5 : PAIEMENTS EN ATTENTE
            // ═══════════════════════════════════════════════════════════════
            if (($stats['en_attente'] ?? 0) > 0) {
                $montantEstime = $prixActuel * $stats['en_attente'];

                $alerts[] = [
                    'type'          => 'warning',
                    'programme_id'  => $programme['idProg'],
                    'programme_nom' => $programme['nom'],
                    'message'       => "💳 PAIEMENTS EN ATTENTE : {$stats['en_attente']} réservation(s) non payée(s) pour \"{$programme['nom']}\" (~" . number_format($montantEstime, 0) . " DT à récupérer)",
                    'action'        => "Relancer immédiatement les {$stats['en_attente']} client(s) concerné(s)",
                    'idees'         => [
                        "📲 WhatsApp personnalisé avec lien de paiement direct",
                        "📧 Email automatique rappel avec deadline 48h",
                        "📞 Appel téléphonique pour les cas > 72h sans paiement",
                        "🔒 Libérer la place automatiquement après 5 jours sans paiement",
                    ],
                    'priority'      => 2,
                    'ai_insight'    => '',
                ];

                $tasks[] = $this->createTask(
                    "Relancer paiements en attente — \"{$programme['nom']}\"",
                    "{$stats['en_attente']} client(s) n'ont pas encore payé (~" . number_format($montantEstime, 0) . " DT). Relance WhatsApp + email.",
                    'high',
                    'programme',
                    $programme['idProg']
                );
            }
        }

        // ─── Enrichissement IA Ollama des alertes ────────────────────────
        if (!empty($alerts) && $this->ollamaService->isAvailable()) {
            $ollamaInsight = $this->ollamaService->generateAlertInsights($ollamaContextData);
            if (!empty($ollamaInsight)) {
                // On attache l'insight global à la première alerte
                $alerts[0]['ai_insight'] = $ollamaInsight;
            }
        }

        // ─── Statistiques globales ───────────────────────────────────────
        $programmesSansResa = $this->connection->fetchOne("
            SELECT COUNT(*) FROM programmes p
            WHERE NOT EXISTS (SELECT 1 FROM reservationprog r WHERE r.idP = p.idProg)
            AND (p.dateFin >= CURDATE() OR p.dateFin IS NULL)
        ");

        if ($programmesSansResa > 0) {
            $tasks[] = $this->createTask(
                "Vue globale : {$programmesSansResa} programme(s) sans aucune réservation",
                "Audit global recommandé : vérifier le prix, la visibilité et le timing de ces programmes.",
                'high',
                'general',
                null
            );
        }

        // ─── Auto-transition : todo → done si alerte fixée ───────────────
        // Une tâche 'todo' passe automatiquement en 'done' si son programme
        // n'a plus le problème qui l'a générée (ex: surcharge résolue).
        $tasks = $this->autoTransitionTasks($tasks, $ollamaContextData);

        // ─── Sauvegarde en session ───────────────────────────────────────
        $_SESSION['ai_alerts']        = $alerts;
        $_SESSION['ai_tasks']         = $tasks;
        $_SESSION['ai_last_analysis'] = date('Y-m-d H:i:s');

        return ['alerts' => $alerts, 'tasks' => $tasks];
    }

    /**
     * Auto-transition : si une tâche 'todo' a été précédemment résolue
     * (le problème n'existe plus dans les données actuelles), elle passe en 'done'.
     */
    private function autoTransitionTasks(array $newTasks, array $currentProgrammeStats): array
    {
        $previousTasks = $_SESSION['ai_tasks'] ?? [];
        if (empty($previousTasks)) {
            return $newTasks;
        }

        // Index des programmes actuellement en alerte
        $currentAlertProgrammes = [];
        foreach ($newTasks as $task) {
            if ($task['entity_id']) {
                $currentAlertProgrammes[$task['entity_id']] = true;
            }
        }

        // Pour chaque tâche précédente en 'doing' ou 'todo'
        foreach ($previousTasks as $prevTask) {
            if (in_array($prevTask['status'], ['todo', 'doing']) && $prevTask['entity_id']) {
                // Si le programme de cette tâche n'est plus en alerte → auto done
                if (!isset($currentAlertProgrammes[$prevTask['entity_id']])) {
                    // Chercher la tâche correspondante dans les nouvelles et la marquer done
                    foreach ($newTasks as &$newTask) {
                        if ($newTask['entity_id'] === $prevTask['entity_id']
                            && $newTask['entity_type'] === $prevTask['entity_type']) {
                            $newTask['status'] = 'done';
                        }
                    }
                    unset($newTask);
                }
            }
            // Si la tâche était 'doing', on conserve ce statut dans les nouvelles tâches
            if ($prevTask['status'] === 'doing') {
                foreach ($newTasks as &$newTask) {
                    if ($newTask['entity_id'] === $prevTask['entity_id']
                        && $newTask['title'] === $prevTask['title']) {
                        $newTask['status'] = 'doing';
                    }
                }
                unset($newTask);
            }
        }

        return $newTasks;
    }

    /**
     * Génère une liste d'idées enrichies pour un programme sans réservation.
     */
    private function buildNoReservationIdeas(
        string $nom,
        float $prixActuel,
        float $prixFlash,
        float $prixEarlyBird,
        int $joursRestants
    ): array {
        $idees = [
            "🔥 Offre FLASH : " . number_format($prixFlash, 2) . " DT (−30%) valable 48h seulement",
            "🐦 Early Bird : " . number_format($prixEarlyBird, 2) . " DT (−15%) pour les 5 premiers inscrits",
            "👥 Offre groupe : −10% supplémentaires pour toute inscription de 3 personnes ou plus",
            "📣 Boost Facebook/Instagram ciblé sur la région avec budget de 50-100 DT",
            "🤳 Story Instagram avec compte à rebours \"{$joursRestants} jours avant le départ !\"",
            "📧 Envoi d'email personnalisé à tous les anciens clients avec offre exclusive",
            "📲 Campagne WhatsApp broadcast vers la liste de contacts qualifiés",
            "🤝 Contacter 2-3 influenceurs voyage tunisiens pour un post sponsorisé",
            "🎁 Inclure un extra gratuit (déjeuner, activité, photo souvenir) sans changer le prix",
            "📰 Publication dans les groupes Facebook voyage & tourisme en Tunisie",
        ];

        if ($joursRestants < 14) {
            array_unshift($idees,
                "🚨 URGENT : Envisager un report ou annulation si 0 inscription dans 72h",
                "💬 Contacter personnellement chaque prospect déjà intéressé par ce programme"
            );
        }

        return $idees;
    }

    private function createTask(string $title, string $description, string $priority, string $entityType, ?string $entityId): array
    {
        $priorities = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];

        return [
            'id'             => uniqid('task_'),
            'title'          => $title,
            'description'    => $description,
            'priority'       => $priority,
            'priority_order' => $priorities[$priority] ?? 4,
            'entity_type'    => $entityType,
            'entity_id'      => $entityId,
            'status'         => 'todo',
            'created_at'     => date('Y-m-d H:i:s'),
        ];
    }

    public function updateTaskStatus(string $taskId, string $newStatus): bool
    {
        if (!isset($_SESSION['ai_tasks'])) {
            return false;
        }

        foreach ($_SESSION['ai_tasks'] as &$task) {
            if ($task['id'] === $taskId) {
                $task['status'] = $newStatus;
                return true;
            }
        }
        return false;
    }

    public function getTasks(): array          { return $_SESSION['ai_tasks']         ?? []; }
    public function getAlerts(): array         { return $_SESSION['ai_alerts']        ?? []; }
    public function getLastAnalysis(): ?string { return $_SESSION['ai_last_analysis'] ?? null; }
}