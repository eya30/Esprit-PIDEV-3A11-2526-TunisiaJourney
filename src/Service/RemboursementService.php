<?php

namespace App\Service;

use App\Entity\ReservationChambre;
use DateTimeInterface;

class RemboursementService
{
    /**
     * Constantes pour les règles de remboursement
     */
    private const DELAI_REMBOURSEMENT_100 = 30;
    private const DELAI_REMBOURSEMENT_50 = 15;
    private const DELAI_REMBOURSEMENT_25 = 7;
   
    /**
     * Calcule le pourcentage de remboursement
     */
    public function calculerPourcentage(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): int
    {
        $dateArrivee = $reservation->getDateDebut();
       
        if ($dateArrivee === null) {
            return 0;
        }
       
        if ($dateAnnulation > $dateArrivee) {
            return 0;
        }
       
        $interval = $dateArrivee->diff($dateAnnulation);
        $joursAvantArrivee = (int) $interval->format('%a');
       
        if ($joursAvantArrivee >= self::DELAI_REMBOURSEMENT_100) {
            return 100;
        }
       
        if ($joursAvantArrivee >= self::DELAI_REMBOURSEMENT_50) {
            return 50;
        }
       
        if ($joursAvantArrivee >= self::DELAI_REMBOURSEMENT_25) {
            return 25;
        }
       
        return 0;
    }
   
    /**
     * Calcule le montant à rembourser
     */
    public function calculerMontant(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): float
    {
        $pourcentage = $this->calculerPourcentage($reservation, $dateAnnulation);
       
        // Récupération du montant total avec vérification
        $montantTotal = $reservation->getPrixTotal();
       
        // Conversion en float si c'est une string
        if ($montantTotal === null) {
            return 0.0;
        }
       
        $montantTotalFloat = is_numeric($montantTotal) ? (float)$montantTotal : 0.0;
       
        return round($montantTotalFloat * $pourcentage / 100, 2);
    }
   
    /**
     * Récupère le texte explicatif
     */
    public function getTexteExplication(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): string
    {
        $dateArrivee = $reservation->getDateDebut();
       
        if ($dateArrivee === null) {
            return "❌ Date d'arrivée non définie : aucun remboursement";
        }
       
        if ($dateAnnulation > $dateArrivee) {
            return "❌ Annulation après la date d'arrivée : aucun remboursement";
        }
       
        $interval = $dateArrivee->diff($dateAnnulation);
        $jours = (int) $interval->format('%a');
       
        if ($jours >= self::DELAI_REMBOURSEMENT_100) {
            return "✅ Annulation {$jours} jours avant l'arrivée (> 30 jours) → remboursement 100%";
        }
       
        if ($jours >= self::DELAI_REMBOURSEMENT_50) {
            return "⚠️ Annulation {$jours} jours avant l'arrivée (15-30 jours) → remboursement 50%";
        }
       
        if ($jours >= self::DELAI_REMBOURSEMENT_25) {
            return "⚠️ Annulation {$jours} jours avant l'arrivée (7-15 jours) → remboursement 25%";
        }
       
        return "❌ Annulation {$jours} jours avant l'arrivée (- de 7 jours) → aucun remboursement";
    }
   
    /**
     * Vérifie si la réservation est remboursable
     */
    public function estRemboursable(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): bool
    {
        return $this->calculerPourcentage($reservation, $dateAnnulation) > 0;
    }
   
    /**
     * Récupère le nombre de jours avant l'arrivée
     */
    public function getJoursAvantArrivee(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): int
    {
        $dateArrivee = $reservation->getDateDebut();
       
        if ($dateArrivee === null) {
            return 0;
        }
       
        if ($dateAnnulation > $dateArrivee) {
            return 0;
        }
       
        return (int) $dateArrivee->diff($dateAnnulation)->format('%a');
    }
   
    /**
     * Récupère la classe CSS pour l'affichage
     */
    public function getCssClass(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): string
    {
        $pourcentage = $this->calculerPourcentage($reservation, $dateAnnulation);
       
        if ($pourcentage >= 100) return 'success';
        if ($pourcentage >= 50) return 'warning';
        if ($pourcentage >= 25) return 'info';
        return 'danger';
    }
   
    /**
     * Récupère l'icône pour l'affichage
     */
    public function getIcone(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): string
    {
        $pourcentage = $this->calculerPourcentage($reservation, $dateAnnulation);
       
        if ($pourcentage >= 100) return 'fa-check-circle';
        if ($pourcentage >= 50) return 'fa-exclamation-triangle';
        if ($pourcentage >= 25) return 'fa-clock';
        return 'fa-times-circle';
    }
   
    /**
     * Récupère TOUTES les informations de remboursement en une fois
     *
     * @return array{
     *     montant_total: float,
     *     montant_rembourse: float,
     *     pourcentage: int,
     *     jours_avant_arrivee: int,
     *     est_remboursable: bool,
     *     explication: string,
     *     css_class: string,
     *     icone: string
     * }
     */
    public function getRemboursementInfo(ReservationChambre $reservation, DateTimeInterface $dateAnnulation): array
    {
        $montantTotal = $reservation->getPrixTotal();
       
        // Conversion en float si c'est une string
        $montantTotalFloat = 0.0;
        if ($montantTotal !== null && is_numeric($montantTotal)) {
            $montantTotalFloat = (float)$montantTotal;
        }
       
        $pourcentage = $this->calculerPourcentage($reservation, $dateAnnulation);
        $montantRembourse = $this->calculerMontant($reservation, $dateAnnulation);
        $jours = $this->getJoursAvantArrivee($reservation, $dateAnnulation);
       
        return [
            'montant_total' => $montantTotalFloat,
            'montant_rembourse' => $montantRembourse,
            'pourcentage' => $pourcentage,
            'jours_avant_arrivee' => $jours,
            'est_remboursable' => $pourcentage > 0,
            'explication' => $this->getTexteExplication($reservation, $dateAnnulation),
            'css_class' => $this->getCssClass($reservation, $dateAnnulation),
            'icone' => $this->getIcone($reservation, $dateAnnulation),
        ];
    }
}
