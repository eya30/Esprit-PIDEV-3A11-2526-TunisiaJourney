<?php
namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: "produit")]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDPR")]
    private ?int $id = null;

    #[ORM\Column(name: "Titre", length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Le titre du produit est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s\-''.,:]+$/u",
        message: "Le titre doit contenir uniquement des lettres, pas de chiffres."
    )]
    private ?string $titre = null;

    #[ORM\Column(name: "Description", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(name: "Stock", nullable: true)]
    #[Assert\NotNull(message: "Le stock est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le stock ne peut pas être négatif.")]
    #[Assert\LessThanOrEqual(
        value: 10000,
        message: "Le stock ne peut pas dépasser {{ compared_value }} unités."
    )]
    private ?int $stock = null;

    #[ORM\Column(name: "Poids", nullable: true)]
    #[Assert\NotNull(message: "Le poids est obligatoire.")]
    #[Assert\Positive(message: "Le poids doit être un nombre positif.")]
    #[Assert\LessThanOrEqual(
        value: 100000,
        message: "Le poids ne peut pas dépasser {{ compared_value }} grammes."
    )]
    private ?int $poids = null;

    #[ORM\Column(name: "Disponibilite", type: "integer", nullable: true)]
    #[Assert\NotNull(message: "La disponibilité est obligatoire.")]
    #[Assert\Choice(
        choices: [0, 1],
        message: "La disponibilité doit être 'En stock' ou 'Rupture'."
    )]
    private ?int $disponibilite = 1;

    #[ORM\Column(name: "Image", length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: "Prix", nullable: true)]
    #[Assert\NotNull(message: "Le prix est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif.")]
    #[Assert\LessThanOrEqual(
        value: 100000,
        message: "Le prix ne peut pas dépasser {{ compared_value }} TND."
    )]
    private ?float $prix = null;

    // ── Getters & Setters ──────────────────────────────────────────────────
    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): self { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getStock(): ?int { return $this->stock; }
    public function setStock(?int $stock): self { $this->stock = $stock; return $this; }

    public function getPoids(): ?int { return $this->poids; }
    public function setPoids(?int $poids): self { $this->poids = $poids; return $this; }

    public function getDisponibilite(): ?int { return $this->disponibilite; }
    public function setDisponibilite(?int $disponibilite): self { $this->disponibilite = $disponibilite; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self { $this->image = $image; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(?float $prix): self { $this->prix = $prix; return $this; }
}
