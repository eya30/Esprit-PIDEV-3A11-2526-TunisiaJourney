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

    #[ORM\Column(name: 'Quantite', nullable: true)]
    #[Assert\Positive(message: "La quantité doit être supérieure à zéro.")]
    private ?int $Quantite = 1; // Valeur par défaut

    #[ORM\Column(name: 'DateC', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $DateC = null;

    #[ORM\Column(name: 'Statut', length: 30, nullable: true)]
    private ?string $Statut = 'En attente';

    #[ORM\Column(name: 'Total', nullable: true)]
    private ?float $Total = 0.0;

    #[ORM\Column(name: 'AdresseLiv', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    #[Assert\Length(min: 5, minMessage: "L'adresse est trop courte.")]
    private ?string $AdresseLiv = null;

    #[ORM\Column(name: 'CodePostal', length: 10, nullable: true)]
    #[Assert\NotBlank(message: "Le code postal est obligatoire.")]
    #[Assert\Regex(pattern: "/^\d{4,10}$/", message: "Le code postal est invalide.")]
    private ?string $CodePostal = null;

    #[ORM\Column(name: 'ModePaiement', length: 50, nullable: true)]
    #[Assert\NotBlank(message: "Le mode de paiement est obligatoire.")]
    private ?string $ModePaiement = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeProduit::class, cascade: ['persist', 'remove'])]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->DateC = new \DateTime(); // Date auto
        $this->Statut = 'En attente';   // Statut auto
    }

    public function getId(): ?int { return $this->id; }
    public function getQuantite(): ?int { return $this->Quantite; }
    public function setQuantite(?int $Quantite): self { $this->Quantite = $Quantite; return $this; }
    public function getDateC(): ?\DateTimeInterface { return $this->DateC; }
    public function setDateC(?\DateTimeInterface $DateC): self { $this->DateC = $DateC; return $this; }
    public function getStatut(): ?string { return $this->Statut; }
    public function setStatut(?string $Statut): self { $this->Statut = $Statut; return $this; }
    public function getTotal(): ?float { return $this->Total; }
    public function setTotal(?float $Total): self { $this->Total = $Total; return $this; }
    public function getAdresseLiv(): ?string { return $this->AdresseLiv; }
    public function setAdresseLiv(?string $AdresseLiv): self { $this->AdresseLiv = $AdresseLiv; return $this; }
    public function getCodePostal(): ?string { return $this->CodePostal; }
    public function setCodePostal(?string $CodePostal): self { $this->CodePostal = $CodePostal; return $this; }
    public function getModePaiement(): ?string { return $this->ModePaiement; }
    public function setModePaiement(?string $ModePaiement): self { $this->ModePaiement = $ModePaiement; return $this; }
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