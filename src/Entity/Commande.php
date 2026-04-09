<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IDCO')]
    private ?int $id = null;

    // ✅ Propriétés en camelCase — le name: mappe vers la vraie colonne BDD
    #[ORM\Column(name: 'Quantite', nullable: true)]
    #[Assert\Positive(message: "La quantité doit être supérieure à zéro.")]
    private ?int $quantite = 1;

    #[ORM\Column(name: 'DateC', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateC = null;

    #[ORM\Column(name: 'Statut', length: 30, nullable: true)]
    private ?string $statut = 'En attente';

    #[ORM\Column(name: 'Total', nullable: true)]
    private ?float $total = 0.0;

    #[ORM\Column(name: 'AdresseLiv', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    #[Assert\Length(min: 5, minMessage: "L'adresse est trop courte.")]
    private ?string $adresseLiv = null;

    #[ORM\Column(name: 'CodePostal', length: 10, nullable: true)]
    #[Assert\NotBlank(message: "Le code postal est obligatoire.")]
    #[Assert\Regex(pattern: "/^\d{4,10}$/", message: "Le code postal est invalide.")]
    private ?string $codePostal = null;

    #[ORM\Column(name: 'ModePaiement', length: 50, nullable: true)]
    #[Assert\NotBlank(message: "Le mode de paiement est obligatoire.")]
    private ?string $modePaiement = null;

    // ✅ Relation User
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeProduit::class, cascade: ['persist', 'remove'])]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes   = new ArrayCollection();
        $this->dateC    = new \DateTime();
        $this->statut   = 'En attente';
    }

    public function getId(): ?int { return $this->id; }

    public function getQuantite(): ?int { return $this->quantite; }
    public function setQuantite(?int $quantite): self { $this->quantite = $quantite; return $this; }

    public function getDateC(): ?\DateTimeInterface { return $this->dateC; }
    public function setDateC(?\DateTimeInterface $dateC): self { $this->dateC = $dateC; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }

    public function getTotal(): ?float { return $this->total; }
    public function setTotal(?float $total): self { $this->total = $total; return $this; }

    public function getAdresseLiv(): ?string { return $this->adresseLiv; }
    public function setAdresseLiv(?string $adresseLiv): self { $this->adresseLiv = $adresseLiv; return $this; }

    public function getCodePostal(): ?string { return $this->codePostal; }
    public function setCodePostal(?string $codePostal): self { $this->codePostal = $codePostal; return $this; }

    public function getModePaiement(): ?string { return $this->modePaiement; }
    public function setModePaiement(?string $modePaiement): self { $this->modePaiement = $modePaiement; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getLignes(): Collection { return $this->lignes; }

    public function addLigne(CommandeProduit $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }
        return $this;
    }
}
