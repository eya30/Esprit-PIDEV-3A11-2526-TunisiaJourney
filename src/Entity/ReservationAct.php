<?php

namespace App\Entity;

use App\Repository\ReservationActRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationActRepository::class)]
#[ORM\Table(name: 'reservationact')]
class ReservationAct
{
    public const STATUS_CONFIRMED = 'confirmé';
    public const STATUS_CANCELLED = 'annulé';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDRes", type: "integer")]
    /** @phpstan-ignore-next-line */
    private ?int $IDRes = null;

    #[ORM\Column(name: "id", type: "integer")]
    private ?int $userId = null;

    #[ORM\Column(name: "IDAct", type: "integer")]
    private ?int $IDAct = null;

    #[ORM\Column(name: "Nom", length: 50)]
    #[Assert\NotBlank(message: "Le nom est requis.")]
    #[Assert\Length(min: 4, minMessage: "Le nom doit contenir au moins {{ limit }} caractères.")]
    private ?string $Nom = null;

    #[ORM\Column(name: "Prenom", length: 50)]
    #[Assert\NotBlank(message: "Le prénom est requis.")]
    #[Assert\Length(min: 4, minMessage: "Le prénom doit contenir au moins {{ limit }} caractères.")]
    private ?string $Prenom = null;

    #[ORM\Column(name: "DateReservation", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $DateReservation = null;

    #[ORM\Column(name: "NombrePlaces", type: "integer", nullable: true)]
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