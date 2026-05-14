<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class FideliteService
{
    private Connection $connection;

    /** @var array<string, int> */
    private array $seuils = [
        'bronze' => 0,
        'argent' => 10000,    // 10 000 points = 1 000 000 DT dépensés
        'or' => 50000,        // 50 000 points = 5 000 000 DT dépensés
        'platine' => 100000,  // 100 000 points = 10 000 000 DT dépensés
    ];

    /** @var array<string, int> */
    private array $reductions = [
        'bronze' => 0,
        'argent' => 5,
        'or' => 10,
        'platine' => 15,
    ];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    // 100 DT = 1 point (car 1000 DT = 10 points)
    private function calculerPoints(float $montant): int
    {
        return intval($montant / 100);
    }

    /**
     * @return array<string, int|string|null>
     */
    public function getOrCreateFidelite(int $idUtilisateur): array
    {
        $sql = "SELECT * FROM fidelite WHERE id_utilisateur = ?";
        $fidelite = $this->connection->fetchAssociative($sql, [$idUtilisateur]);

        if (!$fidelite) {
            $this->connection->insert('fidelite', [
                'id_utilisateur' => $idUtilisateur,
                'points' => 0,
                'niveau' => 'bronze',
                'total_depense' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
           
            return [
                'id_utilisateur' => $idUtilisateur,
                'points' => 0,
                'niveau' => 'bronze',
                'total_depense' => 0,
            ];
        }

        return $fidelite;
    }

    public function ajouterPoints(int $idUtilisateur, float $montant): void
    {
        $pointsGagnes = $this->calculerPoints($montant);
       
        $fidelite = $this->getOrCreateFidelite($idUtilisateur);
       
        $anciensPoints = is_numeric($fidelite['points']) ? (int)$fidelite['points'] : 0;
        $ancienTotal = is_numeric($fidelite['total_depense']) ? (float)$fidelite['total_depense'] : 0.0;
       
        $nouveauxPoints = $anciensPoints + $pointsGagnes;
        $nouveauTotal = $ancienTotal + $montant;
       
        $nouveauNiveau = $this->calculerNiveau($nouveauxPoints);
       
        $this->connection->update('fidelite', [
            'points' => $nouveauxPoints,
            'niveau' => $nouveauNiveau,
            'total_depense' => $nouveauTotal,
            'date_derniere_activite' => date('Y-m-d H:i:s'),
        ], ['id_utilisateur' => $idUtilisateur]);
    }

    public function calculerNiveau(int $points): string
    {
        if ($points >= $this->seuils['platine']) return 'platine';
        if ($points >= $this->seuils['or']) return 'or';
        if ($points >= $this->seuils['argent']) return 'argent';
        return 'bronze';
    }

    public function getReduction(int $idUtilisateur): int
    {
        $fidelite = $this->getOrCreateFidelite($idUtilisateur);
        $niveau = is_string($fidelite['niveau']) ? $fidelite['niveau'] : 'bronze';
        return $this->reductions[$niveau] ?? 0;
    }

    /**
     * @return array{
     *     niveau: string,
     *     points: int,
     *     reduction: int,
     *     total_depense: int|string|null,
     *     prochain_seuil: string|null,
     *     points_restants: int|null,
     *     pourcentage_progression: float,
     *     couleur: string
     * }
     */
    public function getInfosFidelite(int $idUtilisateur): array
    {
        $fidelite = $this->getOrCreateFidelite($idUtilisateur);
       
        $niveau = (string)$fidelite['niveau'];
        $points = (int)$fidelite['points'];
        $reduction = $this->reductions[$niveau] ?? 0;
       
        $prochainSeuil = null;
        $pointsRestants = null;
       
        if ($niveau === 'bronze') {
            $prochainSeuil = 'argent';
            $pointsRestants = $this->seuils['argent'] - $points;
        } elseif ($niveau === 'argent') {
            $prochainSeuil = 'or';
            $pointsRestants = $this->seuils['or'] - $points;
        } elseif ($niveau === 'or') {
            $prochainSeuil = 'platine';
            $pointsRestants = $this->seuils['platine'] - $points;
        }
       
        $pourcentage = 100.0;
        // ✅ Correction ligne 139 : suppression de la condition inutile
        if ($pointsRestants !== null && $pointsRestants > 0) {
            $totalPourProchain = $this->seuils[$prochainSeuil];
            $pourcentage = ($points / $totalPourProchain) * 100;
        }
       
        $couleurs = [
            'bronze' => '#CD7F32',
            'argent' => '#C0C0C0',
            'or' => '#FFD700',
            'platine' => '#E5E4E2',
        ];
       
        return [
            'niveau' => $niveau,
            'points' => $points,
            'reduction' => $reduction,
            'total_depense' => $fidelite['total_depense'],
            'prochain_seuil' => $prochainSeuil,
            'points_restants' => $pointsRestants,
            'pourcentage_progression' => min(100.0, $pourcentage),
            'couleur' => $couleurs[$niveau] ?? '#CD7F32',
        ];
    }
}
