<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ai-planner')]
class AIPlannerController extends AbstractController
{
    public function __construct(
        private Connection $connection,
    ) {}

    #[Route('/', name: 'app_ai_planner')]
    public function index(): Response
    {
        return $this->render('ai_planner/index.html.twig');
    }

    #[Route('/recommendations', name: 'app_ai_recommendations', methods: ['POST'])]
    public function getRecommendations(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dateDebut       = $data['dateDebut'] ?? null;
        $dateFin         = $data['dateFin'] ?? null;
        $typeActivite    = $data['typeActivite'] ?? null;
        $budget          = $data['budget'] ?? null;
        $nombrePersonnes = $data['nombrePersonnes'] ?? null;
        $groupType       = $data['groupType'] ?? 'solo';

        // Validation
        if (!$dateDebut || !$dateFin || !$typeActivite || !$budget || !$nombrePersonnes) {
            return $this->json(['error' => 'Tous les champs sont requis'], 400);
        }

        // Récupérer les activités disponibles
        $activites = $this->getAvailableActivities(
            (string) $dateDebut,
            (string) $dateFin,
            (string) $typeActivite,
            (float) $budget,
            (int) $nombrePersonnes
        );

        // Générer les recommandations
        $recommendations = $this->generateRecommendations(
            $activites,
            (string) $groupType,
            (float) $budget,
            (int) $nombrePersonnes,
            (string) $typeActivite
        );

        return $this->json([
            'success'         => true,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableActivities(
        string $dateDebut,
        string $dateFin,
        string $typeActivite,
        float  $budget,
        int    $nombrePersonnes
    ): array {
        $sql = "
            SELECT a.*, e.Titre as eventTitle, e.DateDebut as eventStart, e.DateFin as eventEnd,
                   COALESCE(SUM(r.NombrePlaces), 0) as totalReserved
            FROM Activite a
            JOIN Evenement e ON a.IDEv = e.IDEv
            LEFT JOIN reservationact r ON r.IDAct = a.IDAct
            WHERE a.Prix <= :budget
            AND a.CapaciteM >= :personnes
            AND a.TypeActivite = :typeActivite
            AND e.DateDebut <= :dateFin
            AND e.DateFin >= :dateDebut
            GROUP BY a.IDAct
            HAVING (a.CapaciteM - COALESCE(SUM(r.NombrePlaces), 0)) >= :personnes
            ORDER BY a.HeureDebut ASC
        ";

        return $this->connection->fetchAllAssociative($sql, [
            'budget'       => $budget,
            'personnes'    => $nombrePersonnes,
            'typeActivite' => $typeActivite,
            'dateDebut'    => $dateDebut,
            'dateFin'      => $dateFin,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $activites
     * @return array<int, array<string, mixed>>
     */
    private function generateRecommendations(
        array  $activites,
        string $groupType,
        float  $budget,
        int    $nombrePersonnes,
        string $typeActivite
    ): array {
        if (empty($activites)) {
            return [
                [
                    'name'            => 'Aucune activité trouvée',
                    'activities'      => [],
                    'message'         => '😔 Désolé, aucune activité de type "' . $this->getActivityTypeName($typeActivite) . '" n\'est disponible pour vos critères. Essayez d\'autres dates ou un budget plus élevé.',
                    'totalCost'       => 0,
                    'remainingBudget' => $budget,
                    'score'           => 0,
                ],
            ];
        }

        // Si une seule activité, on la retourne directement
        if (count($activites) === 1) {
            $activite = $activites[0];
            $cost     = (float) $activite['Prix'] * $nombrePersonnes;

            return [
                [
                    'name'            => $this->getActivityTypeName($typeActivite),
                    'activities'      => [$activite],
                    'totalCost'       => $cost,
                    'remainingBudget' => $budget - $cost,
                    'message'         => $this->getPersonalizedMessage($groupType, 1, $cost, $budget, $typeActivite),
                    'score'           => 100,
                    'isSingle'        => true,
                ],
            ];
        }

        // Sinon, on propose plusieurs combinaisons
        $recommendations = [];

        // 1. Meilleur rapport qualité/prix
        $bestValue = $this->getBestValueRecommendation($activites, $budget, $nombrePersonnes);
        if (!empty($bestValue['activities'])) {
            $bestValue['activities'] = $this->sortActivitiesByDate($bestValue['activities']);
            $recommendations[]       = $bestValue;
        }

        // 2. Plus d'activités possible
        $maxActivities = $this->getMaxActivitiesRecommendation($activites, $budget, $nombrePersonnes);
        if (!empty($maxActivities['activities']) && count($maxActivities['activities']) > count($bestValue['activities'])) {
            $maxActivities['activities'] = $this->sortActivitiesByDate($maxActivities['activities']);
            $recommendations[]           = $maxActivities;
        }

        // 3. Meilleure qualité (prix élevé)
        $bestQuality = $this->getBestQualityRecommendation($activites, $budget, $nombrePersonnes);
        if (!empty($bestQuality['activities']) && (float) $bestQuality['activities'][0]['Prix'] > (float) ($bestValue['activities'][0]['Prix'] ?? 0)) {
            $bestQuality['activities'] = $this->sortActivitiesByDate($bestQuality['activities']);
            $recommendations[]         = $bestQuality;
        }

        // 4. Combinaison premium (2 meilleures activités)
        $premiumCombo = $this->getPremiumCombination($activites, $budget, $nombrePersonnes);
        if (!empty($premiumCombo['activities']) && count($premiumCombo['activities']) >= 2) {
            $premiumCombo['activities'] = $this->sortActivitiesByDate($premiumCombo['activities']);
            // Éviter le doublon avec bestQuality si c'est la même chose
            $isDuplicate = false;
            foreach ($recommendations as $existing) {
                if (count($existing['activities']) === count($premiumCombo['activities'])) {
                    $existingIds = array_column($existing['activities'], 'IDAct');
                    $newIds      = array_column($premiumCombo['activities'], 'IDAct');
                    if ($existingIds == $newIds) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }
            if (!$isDuplicate) {
                $recommendations[] = $premiumCombo;
            }
        }

        // Éviter les doublons
        $uniqueRecs = [];
        foreach ($recommendations as $rec) {
            $key = serialize(array_column($rec['activities'], 'IDAct'));
            if (!isset($uniqueRecs[$key])) {
                $uniqueRecs[$key] = $rec;
            }
        }
        $recommendations = array_values($uniqueRecs);

        // Limiter à 3 maximum
        $recommendations = array_slice($recommendations, 0, 3);

        // Ajouter les messages et scores
        foreach ($recommendations as &$rec) {
            $rec['name']    = $this->getRecommendationName($rec, $typeActivite);
            $rec['message'] = $this->getPersonalizedMessage($groupType, count($rec['activities']), (float) $rec['totalCost'], $budget, $typeActivite);
            $rec['score']   = $this->calculateScore($rec, $budget);
        }

        return $recommendations;
    }

    /**
     * Trie les activités par heure de début
     * @param array<int, array<string, mixed>> $activities
     * @return array<int, array<string, mixed>>
     */
    private function sortActivitiesByDate(array $activities): array
    {
        usort($activities, function ($a, $b) {
            $heureA = (string) ($a['HeureDebut'] ?? '00:00');
            $heureB = (string) ($b['HeureDebut'] ?? '00:00');

            return strcmp($heureA, $heureB);
        });

        return $activities;
    }

    /**
     * @param array<int, array<string, mixed>> $activites
     * @return array<string, mixed>
     */
    private function getBestValueRecommendation(array $activites, float $budget, int $personnes): array
    {
        $selected        = [];
        $remainingBudget = $budget;

        // Trier par prix croissant
        $sorted = $activites;
        usort($sorted, function ($a, $b) {
            return (float) $a['Prix'] <=> (float) $b['Prix'];
        });

        foreach ($sorted as $activite) {
            $cost = (float) $activite['Prix'] * $personnes;
            if ($cost <= $remainingBudget) {
                $selected[]      = $activite;
                $remainingBudget -= $cost;
            }
        }

        return [
            'activities'      => $selected,
            'totalCost'       => $budget - $remainingBudget,
            'remainingBudget' => $remainingBudget,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $activites
     * @return array<string, mixed>
     */
    private function getMaxActivitiesRecommendation(array $activites, float $budget, int $personnes): array
    {
        $selected        = [];
        $remainingBudget = $budget;

        // Trier par prix croissant pour maximiser le nombre
        $sorted = $activites;
        usort($sorted, function ($a, $b) {
            return (float) $a['Prix'] <=> (float) $b['Prix'];
        });

        foreach ($sorted as $activite) {
            $cost = (float) $activite['Prix'] * $personnes;
            if ($cost <= $remainingBudget) {
                $selected[]      = $activite;
                $remainingBudget -= $cost;
            }
        }

        return [
            'activities'      => $selected,
            'totalCost'       => $budget - $remainingBudget,
            'remainingBudget' => $remainingBudget,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $activites
     * @return array<string, mixed>
     */
    private function getBestQualityRecommendation(array $activites, float $budget, int $personnes): array
    {
        // Prendre l'activité la plus chère dans le budget
        $bestActivity    = null;
        $remainingBudget = $budget;

        $sorted = $activites;
        usort($sorted, function ($a, $b) {
            return (float) $b['Prix'] <=> (float) $a['Prix'];
        });

        foreach ($sorted as $activite) {
            $cost = (float) $activite['Prix'] * $personnes;
            if ($cost <= $remainingBudget) {
                $bestActivity    = $activite;
                $remainingBudget -= $cost;
                break;
            }
        }

        if ($bestActivity !== null) {
            return [
                'activities'      => [$bestActivity],
                'totalCost'       => $budget - $remainingBudget,
                'remainingBudget' => $remainingBudget,
            ];
        }

        return ['activities' => [], 'totalCost' => 0, 'remainingBudget' => $budget];
    }

    /**
     * @param array<int, array<string, mixed>> $activites
     * @return array<string, mixed>
     */
    private function getPremiumCombination(array $activites, float $budget, int $personnes): array
    {
        $selected        = [];
        $remainingBudget = $budget;

        // Prendre les 2 meilleures activités (qualité prix)
        $sorted = $activites;
        usort($sorted, function ($a, $b) {
            return (float) $b['Prix'] <=> (float) $a['Prix'];
        });

        $count = 0;
        foreach ($sorted as $activite) {
            if ($count >= 2) {
                break;
            }
            $cost = (float) $activite['Prix'] * $personnes;
            if ($cost <= $remainingBudget) {
                $selected[]      = $activite;
                $remainingBudget -= $cost;
                $count++;
            }
        }

        if (count($selected) >= 2) {
            return [
                'activities'      => $selected,
                'totalCost'       => $budget - $remainingBudget,
                'remainingBudget' => $remainingBudget,
            ];
        }

        return ['activities' => [], 'totalCost' => 0, 'remainingBudget' => $budget];
    }

    /**
     * @param array<string, mixed> $rec
     */
    private function getRecommendationName(array $rec, string $typeActivite): string
    {
        $count    = count($rec['activities']);
        $typeName = $this->getActivityTypeName($typeActivite);

        if ($count === 1) {
            return "🎯 " . $typeName . " - " . $rec['activities'][0]['Titre'];
        } elseif ($count >= 3) {
            return "🌟 Pack " . $typeName . " Complet";
        } elseif ($count === 2) {
            return "💎 Duo " . $typeName . " Premium";
        } else {
            return "✨ Découverte " . $typeName;
        }
    }

    private function getActivityTypeName(string $type): string
    {
        $names = [
            'concert'   => '🎵 Concert',
            'artisanat' => '🎨 Artisanat',
            'cuisine'   => '🍳 Cuisine',
            'spectacle' => '🎭 Spectacle',
            'visite'    => '🏛️ Visite',
            'atelier'   => '✏️ Atelier',
            'sport'     => '⚽ Sport',
        ];

        return $names[$type] ?? $type;
    }

    private function getPersonalizedMessage(string $groupType, int $activitiesCount, float $totalCost, float $budget, string $typeActivite): string
    {
        $savings  = $budget - $totalCost;
        $typeName = $this->getActivityTypeName($typeActivite);

        $messages = [
            'solo' => [
                'perfect' => "🌟 Parfait pour une journée en solo ! Profitez pleinement de cette expérience $typeName.",
                'good'    => "✨ Excellente sélection pour votre journée solo !",
                'budget'  => "💪 Restez dans votre budget tout en vous faisant plaisir !",
            ],
            'couple' => [
                'perfect' => "💖 Idéal pour un couple ! Partager une activité $typeName, c'est magique.",
                'good'    => "💑 Une belle sélection d'activités $typeName à partager !",
                'budget'  => "❤️ Profitez d'une belle journée en amoureux sans dépasser votre budget.",
            ],
            'friends' => [
                'perfect' => "🎉 Entre amis, ça va être mémorable ! Ces activités $typeName sont géniales.",
                'good'    => "👥 Parfait pour une sortie entre amis !",
                'budget'  => "🥳 Amusez-vous sans vous ruiner avec cette sélection.",
            ],
            'family' => [
                'perfect' => "👨‍👩‍👧‍👦 Idéal pour toute la famille ! Une activité $typeName pour tous.",
                'good'    => "🏠 Une excellente journée en famille vous attend !",
                'budget'  => "💝 Profitez de moments en famille tout en maîtrisant votre budget.",
            ],
        ];

        $type = $groupType;
        if (!isset($messages[$type])) {
            $type = 'solo';
        }

        if ($activitiesCount >= 2) {
            $msg = $messages[$type]['perfect'];
        } elseif ($activitiesCount >= 1) {
            $msg = $messages[$type]['good'];
        } else {
            return "🔍 Ajustez vos critères pour découvrir des activités $typeName !";
        }

        // FIX :437 — $activitiesCount > 0 est always true ici (PHPStan l'infère >= 1 d'après le elseif)
        // On garde uniquement la vérification utile sur $savings
        if ($savings > 0) {
            $msg .= " 💰 Il vous reste {$savings} TND pour d'autres dépenses !";
        }

        return $msg;
    }

    /**
     * FIX :445 — was: count($recommendation['activities']) > 0 (always true for int<1,max>)
     *             now: use count() result stored in variable, check >= 2 for diversity bonus
     * @param array<string, mixed> $recommendation
     */
    private function calculateScore(array $recommendation, float $budget): float
    {
        $score         = 0.0;
        $activityCount = count($recommendation['activities']);

        // Nombre d'activités (max 40 points)
        $score += min($activityCount * 20, 40);

        // Utilisation du budget (max 40 points)
        if ($budget > 0.0) {
            $budgetUsage = (float) $recommendation['totalCost'] / $budget;
            $score       += $budgetUsage * 40;
        }

        // Bonus diversité (max 20 points)
        // FIX :445 — remplacé "count(...) > 0" (always true) par ">= 2" (logiquement correct)
        if ($activityCount >= 2) {
            $score += 20;
        }

        return min($score, 100.0);
    }
}