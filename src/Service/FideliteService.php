<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class FideliteService
{
    private $connection;

    // NOUVEAUX SEUILS : 1000 DT = 10 points → 100 DT = 1 point
    private $seuils = [
        'bronze' => 0,
        'argent' => 10000,    // 10 000 points = 1 000 000 DT dépensés
        'or' => 50000,        // 50 000 points = 5 000 000 DT dépensés
        'platine' => 100000,  // 100 000 points = 10 000 000 DT dépensés
    ];

    private $reductions = [
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
        $nouveauxPoints = $fidelite['points'] + $pointsGagnes;
        $nouveauTotal = $fidelite['total_depense'] + $montant;
        
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
        return $this->reductions[$fidelite['niveau']] ?? 0;
    }

    public function getInfosFidelite(int $idUtilisateur): array
    {
        $fidelite = $this->getOrCreateFidelite($idUtilisateur);
        
        $niveau = $fidelite['niveau'];
        $points = $fidelite['points'];
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
        
        $pourcentage = 100;
        if ($pointsRestants && $pointsRestants > 0 && $prochainSeuil) {
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
            'pourcentage_progression' => min(100, $pourcentage),
            'couleur' => $couleurs[$niveau] ?? '#CD7F32',
        ];
    }
}