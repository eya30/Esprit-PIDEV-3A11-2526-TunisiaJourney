<?php

namespace App\Entity;

use App\Repository\ReservationActRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationActRepository::class)]
<<<<<<< HEAD
#[ORM\Table(name: 'reservationact')]
class ReservationAct
{
=======
#[ORM\Table(name: 'ReservationAct')]
class ReservationAct
{
    // ── Constantes pour les statuts ──────────────────────────────────────────
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public const STATUS_CONFIRMED = 'confirmé';
    public const STATUS_CANCELLED = 'annulé';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDRes", type: "integer")]
<<<<<<< HEAD
    /** @phpstan-ignore-next-line */
    private ?int $IDRes = null;

    #[ORM\Column(name: "id", type: "integer")]
    private ?int $userId = null;
=======
    private ?int $IDRes = null;

    #[ORM\Column(name: "id", length: 50)]
    private ?string $userId = null;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

    #[ORM\Column(name: "IDAct", type: "integer")]
    private ?int $IDAct = null;

<<<<<<< HEAD
    #[ORM\Column(name: "Nom", length: 50)]
    #[Assert\NotBlank(message: "Le nom est requis.")]
    #[Assert\Length(min: 4, minMessage: "Le nom doit contenir au moins {{ limit }} caractères.")]
    private ?string $Nom = null;

    #[ORM\Column(name: "Prenom", length: 50)]
    #[Assert\NotBlank(message: "Le prénom est requis.")]
    #[Assert\Length(min: 4, minMessage: "Le prénom doit contenir au moins {{ limit }} caractères.")]
=======
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le nom est requis.")]
    #[Assert\Length(
        min: 4,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[A-Za-zÀ-ÿ\s\-]+$/u',
        message: "Le nom ne doit contenir que des lettres, espaces ou tirets."
    )]
    private ?string $Nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le prénom est requis.")]
    #[Assert\Length(
        min: 4,
        minMessage: "Le prénom doit contenir au moins {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[A-Za-zÀ-ÿ\s\-]+$/u',
        message: "Le prénom ne doit contenir que des lettres, espaces ou tirets."
    )]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private ?string $Prenom = null;

    #[ORM\Column(name: "DateReservation", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $DateReservation = null;

    #[ORM\Column(name: "NombrePlaces", type: "integer", nullable: true)]
<<<<<<< HEAD
    #[Assert\Positive(message: "Le nombre de personnes doit être supérieur à 0.")]
    private ?int $NombrePlaces = null;

    #[ORM\Column(name: "prix", type: "float", nullable: true)]
    private ?float $Prix = null;

    #[ORM\Column(name: "email", length: 300)]
    #[Assert\NotBlank(message: "L'email est requis.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(name: "telephone", length: 8)]
    #[Assert\NotBlank(message: "Le téléphone est requis.")]
    #[Assert\Length(exactly: 8, exactMessage: "Le téléphone doit contenir exactement {{ limit }} chiffres.")]
    private ?string $telephone = null;

    public function getIDRes(): ?int { return $this->IDRes; }
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(int $userId): static { $this->userId = $userId; return $this; }
    public function getIDAct(): ?int { return $this->IDAct; }
    public function setIDAct(int $IDAct): static { $this->IDAct = $IDAct; return $this; }
    public function getNom(): ?string { return $this->Nom; }
    public function setNom(?string $Nom): static { $this->Nom = $Nom; return $this; }
    public function getPrenom(): ?string { return $this->Prenom; }
    public function setPrenom(?string $Prenom): static { $this->Prenom = $Prenom; return $this; }
    public function getDateReservation(): ?\DateTimeInterface { return $this->DateReservation; }
    public function setDateReservation(\DateTimeInterface $DateReservation): static { $this->DateReservation = $DateReservation; return $this; }
    public function getNombrePlaces(): ?int { return $this->NombrePlaces; }
    public function setNombrePlaces(?int $NombrePlaces): static { $this->NombrePlaces = $NombrePlaces; return $this; }
    public function getPrix(): ?float { return $this->Prix; }
    public function setPrix(?float $Prix): static { $this->Prix = $Prix; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }
}
=======
    #[Assert\NotBlank(message: "Le nombre de personnes est requis.")]
    #[Assert\Positive(message: "Le nombre de personnes doit être supérieur à 0.")]
    #[Assert\Type(type: "integer", message: "Le nombre de personnes doit être un nombre entier.")]
    #[Assert\LessThanOrEqual(
        value: 100,
        message: "Le nombre de personnes ne peut pas dépasser {{ compared_value }}."
    )]
    private ?int $NombrePlaces = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $Prix = null;

    #[ORM\Column(length: 300)]
    #[Assert\NotBlank(message: "L'email est requis.")]
    #[Assert\Email(
        message: "L'email '{{ value }}' n'est pas valide. Exemple: nom@domaine.com"
    )]
    private ?string $email = null;

    #[ORM\Column(length: 8)]
    #[Assert\NotBlank(message: "Le téléphone est requis.")]
    #[Assert\Length(
        exactly: 8,
        exactMessage: "Le téléphone doit contenir exactement {{ limit }} chiffres."
    )]
    #[Assert\Regex(
        pattern: '/^\d{8}$/',
        message: "Le téléphone ne doit contenir que des chiffres (8 chiffres)."
    )]
    private ?string $telephone = null;

    // ── NOUVEAU CHAMP STATUS ─────────────────────────────────────────────────
    #[ORM\Column(type: "string", length: 20, options: ["default" => "confirmé"])]
    private ?string $status = self::STATUS_CONFIRMED;

    // ── Getters / Setters ────────────────────────────────────────────────────

    public function getIDRes(): ?int { return $this->IDRes; }

    public function getUserId(): ?string { return $this->userId; }
    public function setUserId(string $userId): static { $this->userId = $userId; return $this; }

    public function getIDAct(): ?int { return $this->IDAct; }
    public function setIDAct(int $IDAct): static { $this->IDAct = $IDAct; return $this; }

    public function getNom(): ?string { return $this->Nom; }
    public function setNom(?string $Nom): static { $this->Nom = $Nom; return $this; }

    public function getPrenom(): ?string { return $this->Prenom; }
    public function setPrenom(?string $Prenom): static { $this->Prenom = $Prenom; return $this; }

    public function getDateReservation(): ?\DateTimeInterface { return $this->DateReservation; }
    public function setDateReservation(\DateTimeInterface $DateReservation): static { $this->DateReservation = $DateReservation; return $this; }

    public function getNombrePlaces(): ?int { return $this->NombrePlaces; }
    public function setNombrePlaces(?int $NombrePlaces): static { $this->NombrePlaces = $NombrePlaces; return $this; }

    public function getPrix(): ?float { return $this->Prix; }
    public function setPrix(?float $Prix): static { $this->Prix = $Prix; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    // ── NOUVEAUX GETTERS/SETTERS POUR STATUS ─────────────────────────────────
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [self::STATUS_CONFIRMED, self::STATUS_CANCELLED])) {
            throw new \InvalidArgumentException("Statut invalide. Valeurs acceptées : 'confirmé', 'annulé'");
        }
        $this->status = $status;
        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
