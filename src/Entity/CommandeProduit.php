<?php

namespace App\Entity;

use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeProduitRepository::class)]
#[ORM\Table(name: 'commande_produit')]
class CommandeProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(name: 'IDCO', referencedColumnName: 'IDCO', nullable: true)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'IDPR', referencedColumnName: 'IDPR', nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(name: 'Quantite', type: 'integer', options: ['default' => 1])]
    private int $quantite = 1;

    #[ORM\Column(name: 'is_panier', type: 'boolean', options: ['default' => false])]
    private bool $isPanier = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'taille', type: 'string', length: 5, nullable: true)]
    private ?string $taille = null;

    public function getId(): ?int { return $this->id; }

    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $commande): self { $this->commande = $commande; return $this; }

    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $produit): self { $this->produit = $produit; return $this; }

    public function getQuantite(): int { return $this->quantite; }
    public function setQuantite(int $quantite): self { $this->quantite = $quantite; return $this; }

    public function isPanier(): bool { return $this->isPanier; }
    public function setIsPanier(bool $isPanier): self { $this->isPanier = $isPanier; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getSousTotal(): float
    {
        return ($this->produit?->getPrix() ?? 0) * $this->quantite;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille;
        return $this;
    }
}