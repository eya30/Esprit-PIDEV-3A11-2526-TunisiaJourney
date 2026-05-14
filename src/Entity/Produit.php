<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
class Produit
{
   #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(name: 'IDPR', type: 'integer')]
/** @phpstan-ignore property.unusedType */
private ?int $idPR = null;

    #[ORM\Column(name: 'Titre', type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $titre = null;

    #[ORM\Column(name: 'Description', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, max: 255)]
    private ?string $description = null;

    #[ORM\Column(name: 'Stock', type: 'integer', nullable: true)]
    #[Assert\NotNull(message: "Le stock est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le stock ne peut pas être négatif.")]
    private ?int $stock = null;

    #[ORM\Column(name: 'Poids', type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le poids ne peut pas être négatif.")]
    private ?int $poids = null;

    #[ORM\Column(name: 'Disponibilite', type: 'boolean', options: ['default' => 1])]
    private bool $disponibilite = true;

    #[ORM\Column(name: 'Image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'Prix', type: 'float', nullable: true)]
    #[Assert\NotNull(message: "Le prix est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit être supérieur à zéro.")]
    private ?float $prix = null;

    #[ORM\Column(name: 'Categorie', type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank(message: "La catégorie est obligatoire.")]
    private ?string $categorie = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    public const CATEGORIES = [
        'Artisanat'   => 'Artisanat',
        'Alimentaire' => 'Alimentaire',
        'Bijoux'      => 'Bijoux',
        'Vetements'   => 'Vêtements',
        'Decoration'  => 'Décoration',
    ];

    // ✅ Seuil d'alerte stock faible
    public const SEUIL_STOCK_FAIBLE = 5;

    // ✅ Seuil de suggestion réapprovisionnement
    public const SEUIL_REAPPRO = 10;

    public function getIdPR(): ?int { return $this->idPR; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): static { $this->titre = $titre; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getStock(): ?int { return $this->stock; }
    public function setStock(?int $stock): static { $this->stock = $stock; return $this; }
    public function getPoids(): ?int { return $this->poids; }
    public function setPoids(?int $poids): static { $this->poids = $poids; return $this; }
    public function isDisponibilite(): bool { return $this->disponibilite; }
    public function setDisponibilite(bool $disponibilite): static { $this->disponibilite = $disponibilite; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }
    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(?float $prix): static { $this->prix = $prix; return $this; }
    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(?string $categorie): static { $this->categorie = $categorie; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getUserId(): ?int { return $this->user?->getId(); }

    // ✅ Stock faible ?
    public function isStockFaible(): bool
    {
        return ($this->stock ?? 0) <= self::SEUIL_STOCK_FAIBLE && ($this->stock ?? 0) > 0;
    }

    // ✅ Rupture totale ?
    public function isEnRupture(): bool
    {
        return ($this->stock ?? 0) <= 0;
    }

    // ✅ Nécessite réapprovisionnement ?
    public function needsReappro(): bool
    {
        return ($this->stock ?? 0) <= self::SEUIL_REAPPRO;
    }

    // ✅ Diminuer le stock — retourne false si stock insuffisant
    public function decrementStock(int $quantite): bool
    {
        if (($this->stock ?? 0) < $quantite) {
            return false; // Stock insuffisant
        }
        $this->stock -= $quantite;

        // Bloquer automatiquement si plus de stock
        if ($this->stock <= 0) {
            $this->stock = 0;
            $this->disponibilite = false;
        }

        return true;
    }

    // ✅ Vérifier si quantité commandable
    public function isCommandable(int $quantite = 1): bool
    {
        return $this->disponibilite && ($this->stock ?? 0) >= $quantite;
    }
}