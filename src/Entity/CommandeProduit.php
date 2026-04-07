<?php
namespace App\Entity;

use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeProduitRepository::class)]
#[ORM\Table(name: 'commande_produit')]
class CommandeProduit
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(name: 'IDCO', referencedColumnName: 'IDCO', nullable: false)]
    private ?Commande $commande = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'IDPR', referencedColumnName: 'IDPR', nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(name: 'Quantite')]
    private ?int $quantite = null;

    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $commande): self { $this->commande = $commande; return $this; }

    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $produit): self { $this->produit = $produit; return $this; }

    public function getQuantite(): ?int { return $this->quantite; }
    public function setQuantite(int $quantite): self { $this->quantite = $quantite; return $this; }
}
