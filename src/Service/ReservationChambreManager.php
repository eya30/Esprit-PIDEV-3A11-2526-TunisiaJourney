<?php

namespace App\Service;

use App\Entity\ReservationChambre;

class ReservationChambreManager
{
    public function validate(ReservationChambre $reservation): bool
    {
        // --- idUtilisateur ---
        if (empty($reservation->getIdUtilisateur()) || $reservation->getIdUtilisateur() <= 0) {
            throw new \InvalidArgumentException("L'ID utilisateur est obligatoire et doit être positif.");
        }

        // --- idCh ---
        if (empty($reservation->getIdCh()) || $reservation->getIdCh() <= 0) {
            throw new \InvalidArgumentException("L'ID de la chambre est obligatoire et doit être positif.");
        }

        // --- dateDebut ---
        if ($reservation->getDateDebut() === null) {
            throw new \InvalidArgumentException("La date de début est obligatoire.");
        }
        $today = new \DateTime('today');
        if ($reservation->getDateDebut() < $today) {
            throw new \InvalidArgumentException("La date de début ne peut pas être dans le passé.");
        }

        // --- dateFin ---
        if ($reservation->getDateFin() === null) {
            throw new \InvalidArgumentException("La date de fin est obligatoire.");
        }
        if ($reservation->getDateFin() <= $reservation->getDateDebut()) {
            throw new \InvalidArgumentException("La date de fin doit être postérieure à la date de début.");
        }

        // --- nbNuit ---
        if ($reservation->getNbNuit() === null || $reservation->getNbNuit() <= 0) {
            throw new \InvalidArgumentException("Le nombre de nuits est obligatoire et doit être positif.");
        }
        if ($reservation->getNbNuit() > 90) {
            throw new \InvalidArgumentException("Le séjour ne peut pas dépasser 90 nuits.");
        }

        // --- prixTotal ---
        if ($reservation->getPrixTotal() === null || (float)$reservation->getPrixTotal() <= 0) {
            throw new \InvalidArgumentException("Le prix total est obligatoire et doit être positif.");
        }
        if ((float)$reservation->getPrixTotal() > 100000) {
            throw new \InvalidArgumentException("Le prix total ne peut pas dépasser 100 000 €.");
        }

        // --- nbPersonnes ---
        if ($reservation->getNbPersonnes() === null) {
            throw new \InvalidArgumentException("Le nombre de personnes est obligatoire.");
        }
        if ($reservation->getNbPersonnes() < 1 || $reservation->getNbPersonnes() > 10) {
            throw new \InvalidArgumentException("Le nombre de personnes doit être compris entre 1 et 10.");
        }

        // --- detailsPrix ---
        if (empty($reservation->getDetailsPrix())) {
            throw new \InvalidArgumentException("Le détail du prix est obligatoire.");
        }
        if (strlen($reservation->getDetailsPrix()) > 255) {
            throw new \InvalidArgumentException("Le détail du prix ne peut pas dépasser 255 caractères.");
        }

        // --- telephone ---
        if (empty($reservation->getTelephone())) {
            throw new \InvalidArgumentException("Le numéro de téléphone est obligatoire.");
        }
        $lenTel = strlen($reservation->getTelephone());
        if ($lenTel < 8 || $lenTel > 20) {
            throw new \InvalidArgumentException("Le numéro de téléphone doit contenir entre 8 et 20 chiffres.");
        }
        if (!preg_match("/^[0-9+\-\s]+$/", $reservation->getTelephone())) {
            throw new \InvalidArgumentException("Le numéro de téléphone ne doit contenir que des chiffres, espaces, tirets ou le signe +.");
        }

        // --- statut ---
        $statutsValides = ['confirmé', 'en_attente', 'annulé', 'terminé'];
        if (empty($reservation->getStatut())) {
            throw new \InvalidArgumentException("Le statut est obligatoire.");
        }
        if (!in_array($reservation->getStatut(), $statutsValides)) {
            throw new \InvalidArgumentException("Le statut doit être : confirmé, en_attente, annulé ou terminé.");
        }

        // --- nom (nullable, 2-100, regex) ---
        if ($reservation->getNom() !== null) {
            $lenNom = strlen($reservation->getNom());
            if ($lenNom < 2 || $lenNom > 100) {
                throw new \InvalidArgumentException("Le nom doit contenir entre 2 et 100 caractères.");
            }
            if (!preg_match("/^[a-zA-ZÀ-ÿ\s\'-]+$/u", $reservation->getNom())) {
                throw new \InvalidArgumentException("Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");
            }
        }

        // --- prenom (nullable, 2-100, regex) ---
        if ($reservation->getPrenom() !== null) {
            $lenPrenom = strlen($reservation->getPrenom());
            if ($lenPrenom < 2 || $lenPrenom > 100) {
                throw new \InvalidArgumentException("Le prénom doit contenir entre 2 et 100 caractères.");
            }
            if (!preg_match("/^[a-zA-ZÀ-ÿ\s\'-]+$/u", $reservation->getPrenom())) {
                throw new \InvalidArgumentException("Le prénom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");
            }
        }

        // --- email (nullable) ---
        // IMPORTANT : vérifier la longueur EN PREMIER, avant filter_var
        // car filter_var rejette les parties locales > 64 chars (RFC 5321)
        // ce qui ferait lever la mauvaise exception si on inverse l'ordre
        if ($reservation->getEmail() !== null) {
            if (strlen($reservation->getEmail()) > 150) {
                throw new \InvalidArgumentException("L'email ne peut pas dépasser 150 caractères.");
            }
            if (!filter_var($reservation->getEmail(), FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("L'adresse email n'est pas valide.");
            }
        }

        // --- montantRembourse (nullable) ---
        if ($reservation->getMontantRembourse() !== null) {
            if ((float)$reservation->getMontantRembourse() < 0) {
                throw new \InvalidArgumentException("Le montant remboursé doit être positif ou zéro.");
            }
            if ((float)$reservation->getMontantRembourse() > (float)$reservation->getPrixTotal()) {
                throw new \InvalidArgumentException("Le montant remboursé ne peut pas dépasser le prix total.");
            }
        }

        return true;
    }
}