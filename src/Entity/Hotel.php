<?php

namespace App\Entity;

use App\Repository\HotelRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotelRepository::class)]
class Hotel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $idH = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: "Le nom de l'hôtel est requis.")]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\'-]+$/",
        message: "Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets."
    )]
    private string $nom = '';

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: "La ville est requise.")]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: "La ville doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La ville ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\'-]+$/",
        message: "La ville ne doit contenir que des lettres, espaces, apostrophes ou tirets."
    )]
    private string $ville = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $adresse = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Le nombre d'étoiles est requis.")]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "Les étoiles doivent être comprises entre {{ min }} et {{ max }}."
    )]
    private ?int $etoiles = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La description est requise.")]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: "La promotion doit être comprise entre {{ min }} et {{ max }}%."
    )]
    private ?float $promotion = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Image(
        maxSize: "5M",
        mimeTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        mimeTypesMessage: "L'image doit être au format JPG, PNG, GIF ou WEBP.",
        maxSizeMessage: "L'image ne doit pas dépasser {{ maxSize }}."
    )]
    private ?string $image = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ["disponible", "indisponible", "maintenance"],
        message: "Le status doit être : disponible, indisponible ou maintenance."
    )]
    private ?string $status = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "L'ID utilisateur est requis.")]
    #[Assert\Positive(message: "L'ID utilisateur doit être un nombre positif.")]
    private int $idUtilisateur = 0;

    // Pas de setter pour $idH car il est auto-généré par Doctrine
    public function getIdH(): ?int
    {
        return $this->idH;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getEtoiles(): ?int
    {
        return $this->etoiles;
    }

    public function setEtoiles(?int $etoiles): static
    {
        $this->etoiles = $etoiles;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPromotion(): ?float
    {
        return $this->promotion;
    }

    public function setPromotion(?float $promotion): static
    {
        $this->promotion = $promotion;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->idUtilisateur;
    }

    public function setIdUtilisateur(int $idUtilisateur): static
    {
        $this->idUtilisateur = $idUtilisateur;
        return $this;
    }
}
