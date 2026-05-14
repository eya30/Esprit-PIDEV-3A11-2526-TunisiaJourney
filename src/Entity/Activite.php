<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDAct", type: "integer")]
    /** @phpstan-ignore-next-line */
    private ?int $IDAct = null;

    #[ORM\Column(name: "Titre", length: 100)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 6, minMessage: "Le titre doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(pattern: '/^[^0-9]*$/', message: "Le titre ne peut pas contenir de chiffres.")]
    private ?string $Titre = null;

    #[ORM\Column(name: "Description", length: 1000, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 15, minMessage: "La description doit contenir au moins {{ limit }} caractères.")]
    private ?string $Description = null;

    #[ORM\Column(name: "TypeActivite", length: 50)]
    #[Assert\NotBlank(message: "Le type d'activité est obligatoire.")]
    private ?string $TypeActivite = null;

    #[ORM\Column(name: "HeureDebut", length: 20)]
    #[Assert\NotBlank(message: "L'heure de début est obligatoire.")]
    private ?string $HeureDebut = null;

    #[ORM\Column(name: "Duree", length: 20)]
    #[Assert\NotBlank(message: "La durée est obligatoire.")]
    private ?string $Duree = null;

    #[ORM\Column(name: "NomAnimateur", length: 100)]
    #[Assert\NotBlank(message: "Le nom de l'animateur est obligatoire.")]
    private ?string $NomAnimateur = null;

    #[ORM\Column(name: "CapaciteM", type: "integer", nullable: true)]
    private ?int $CapaciteM = null;

    #[ORM\Column(name: "prix", type: "float", nullable: true)]
    private ?float $Prix = null;

    #[ORM\Column(name: "image", length: 255, nullable: true)]
    private ?string $Image = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'activites')]
    #[ORM\JoinColumn(name: "IDEv", referencedColumnName: "IDEv", nullable: false)]
    private ?Evenement $evenement = null;

    public function getIDAct(): ?int { return $this->IDAct; }
    public function getTitre(): ?string { return $this->Titre; }
    public function setTitre(string $Titre): static { $this->Titre = $Titre; return $this; }
    public function getDescription(): ?string { return $this->Description; }
    public function setDescription(string $Description): static { $this->Description = $Description; return $this; }
    public function getTypeActivite(): ?string { return $this->TypeActivite; }
    public function setTypeActivite(string $TypeActivite): static { $this->TypeActivite = $TypeActivite; return $this; }
    public function getHeureDebut(): ?string { return $this->HeureDebut; }
    public function setHeureDebut(string $HeureDebut): static { $this->HeureDebut = $HeureDebut; return $this; }
    public function getDuree(): ?string { return $this->Duree; }
    public function setDuree(string $Duree): static { $this->Duree = $Duree; return $this; }
    public function getNomAnimateur(): ?string { return $this->NomAnimateur; }
    public function setNomAnimateur(string $NomAnimateur): static { $this->NomAnimateur = $NomAnimateur; return $this; }
    public function getCapaciteM(): ?int { return $this->CapaciteM; }
    public function setCapaciteM(?int $CapaciteM): static { $this->CapaciteM = $CapaciteM; return $this; }
    public function getPrix(): ?float { return $this->Prix; }
    public function setPrix(?float $Prix): static { $this->Prix = $Prix; return $this; }
    public function getImage(): ?string { return $this->Image; }
    public function setImage(?string $Image): static { $this->Image = $Image; return $this; }
    public function getEvenement(): ?Evenement { return $this->evenement; }
    public function setEvenement(?Evenement $evenement): static { $this->evenement = $evenement; return $this; }
}