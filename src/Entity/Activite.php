<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'Activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDAct", type: "integer")]
    /** @phpstan-ignore-next-line */
    private ?int $IDAct = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 6, minMessage: "Le titre doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(pattern: '/^[^0-9]*$/', message: "Le titre ne peut pas contenir de chiffres.")]
    private string $Titre = '';

    #[ORM\Column(length: 300)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 15, minMessage: "La description doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(pattern: '/[a-zA-ZÀ-ÿ]/', message: "La description doit contenir au moins une lettre.")]
    private string $Description = '';

    #[ORM\Column(name: "TypeActivite", length: 50)]
    #[Assert\NotBlank(message: "Le type d'activité est obligatoire.")]
    #[Assert\Length(min: 5, minMessage: "Le type d'activité doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(pattern: '/^[^0-9]*$/', message: "Le type d'activité ne peut pas contenir de chiffres.")]
    private string $TypeActivite = '';

    #[ORM\Column(name: "HeureDebut", length: 20)]
    #[Assert\NotBlank(message: "L'heure de début est obligatoire.")]
    #[Assert\Regex(pattern: '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', message: "L'heure de début doit être au format HH:MM (ex: 09:30).")]
    private string $HeureDebut = '';

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "La durée est obligatoire.")]
    #[Assert\Length(min: 2, minMessage: "La durée doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(pattern: '/^(\d+h(?:\d+min)?|\d+min)$/', message: "La durée doit être au format XhYmin, Xh ou Xmin (ex: 2h30min, 1h, 45min).")]
    private string $Duree = '';

    #[ORM\Column(name: "NomAnimateur", length: 100)]
    #[Assert\NotBlank(message: "Le nom de l'animateur est obligatoire.")]
    #[Assert\Length(min: 5, minMessage: "Le nom de l'animateur doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(pattern: '/^[^0-9]*$/', message: "Le nom de l'animateur ne peut pas contenir de chiffres.")]
    private string $NomAnimateur = '';

    #[ORM\Column(name: "CapaciteM", type: "integer", nullable: true)]
    #[Assert\NotBlank(message: "La capacité est obligatoire.")]
    #[Assert\Type(type: "integer", message: "La capacité doit être un nombre entier.")]
    #[Assert\Positive(message: "La capacité doit être supérieure à 0.")]
    private ?int $CapaciteM = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")]
    #[Assert\Type(type: "float", message: "Le prix doit être un nombre valide.")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Le prix doit être supérieur ou égal à 0.")]
    private ?float $Prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Image = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'activites')]
    #[ORM\JoinColumn(name: "evenement_id", referencedColumnName: "IDEv", nullable: false)]
    #[Assert\NotNull(message: "L'événement associé est obligatoire.")]
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