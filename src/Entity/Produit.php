<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IDPR', type: 'integer')]
    private ?int $idPR = null;

    #[ORM\Column(name: 'Titre', type: 'string', length: 100, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(name: 'Description', type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'Stock', type: 'integer', nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(name: 'Poids', type: 'integer', nullable: true)]
    private ?int $poids = null;

    #[ORM\Column(name: 'Disponibilite', type: 'boolean', options: ['default' => 1])]
    private bool $disponibilite = true;

    #[ORM\Column(name: 'Image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'Prix', type: 'float', nullable: true)]
    private ?float $prix = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId;

    // ✅ Categorie — simple champ VARCHAR, pas de table séparée
    #[ORM\Column(name: 'Categorie', type: 'string', length: 50, nullable: true)]
    private ?string $categorie = null;

    public const CATEGORIES = [
        'Artisanat'   => 'Artisanat',
        'Alimentaire' => 'Alimentaire',
        'Bijoux'      => 'Bijoux',
        'Vêtements'   => 'Vêtements',
        'Décoration'  => 'Décoration',
    ];

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
    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): static { $this->userId = $userId; return $this; }
    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(?string $categorie): static { $this->categorie = $categorie; return $this; }
}