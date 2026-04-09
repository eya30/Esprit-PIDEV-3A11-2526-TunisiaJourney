<?php

namespace App\Entity;

use App\Repository\ReservationActRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationActRepository::class)]
#[ORM\Table(name: 'ReservationAct')]
class ReservationAct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDRes", type: "integer")]
    private ?int $IDRes = null;

    #[ORM\Column(name: "id", length: 50)]
    private ?string $userId = null;

    #[ORM\Column(name: "IDAct", type: "integer")]
    private ?int $IDAct = null;

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
    private ?string $Prenom = null;

    #[ORM\Column(name: "DateReservation", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $DateReservation = null;

    #[ORM\Column(name: "NombrePlaces", type: "integer", nullable: true)]
    #[Assert\NotBlank(message: "Le nombre de personnes est requis.")]
    #[Assert\Positive(message: "Le nombre de personnes doit être supérieur à 0.")]
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
}