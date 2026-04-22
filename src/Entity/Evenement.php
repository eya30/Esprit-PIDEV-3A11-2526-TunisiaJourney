<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'Evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDEv", type: "integer")]
    private ?int $IDEv = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 8,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[^0-9]*$/',
        message: "Le titre ne peut pas contenir de chiffres."
    )]
    private ?string $Titre = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 15,
        minMessage: "La description doit contenir au moins {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: '/[a-zA-ZÀ-ÿ]/',
        message: "La description doit contenir au moins une lettre."
    )]
    private ?string $Description = null;

    #[ORM\Column(name: "DateDebut", type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotNull(message: "La date de début est obligatoire.")]
    #[Assert\Type(
        type: "\DateTimeInterface",
        message: "La date de début doit être une date valide."
    )]
    private ?\DateTimeInterface $DateDebut = null;

    #[ORM\Column(name: "DateFin", type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotNull(message: "La date de fin est obligatoire.")]
    #[Assert\Type(
        type: "\DateTimeInterface",
        message: "La date de fin doit être une date valide."
    )]
    #[Assert\GreaterThanOrEqual(
        propertyPath: "DateDebut",
        message: "La date de fin doit être supérieure ou égale à la date de début."
    )]
    private ?\DateTimeInterface $DateFin = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    #[Assert\Regex(
        pattern: '/[a-zA-ZÀ-ÿ]/',
        message: "Le lieu doit contenir au moins une lettre."
    )]
    #[Assert\Regex(
        pattern: '/^\d+$/',
        match: false,
        message: "Le lieu ne peut pas être uniquement des chiffres."
    )]
    private ?string $Lieu = null;

    #[ORM\Column(name: "CapaciteMax", type: "integer", nullable: true)]
    #[Assert\NotNull(message: "La capacité est obligatoire.")]
    #[Assert\Type(
        type: "integer",
        message: "La capacité doit être un nombre entier."
    )]
    #[Assert\Positive(message: "La capacité doit être supérieure à 0.")]
    private ?int $CapaciteMax = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $Image = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: "L'organisateur est obligatoire.")]
    #[Assert\Length(
        min: 8,
        minMessage: "L'organisateur doit contenir au moins {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[^0-9]*$/',
        message: "L'organisateur ne peut pas contenir de chiffres."
    )]
    private ?string $Organisateur = null;

    #[ORM\Column(name: "id", type: "string", length: 50)]
    private ?string $userId = null;

    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Activite::class, cascade: ['persist', 'remove'])]
    private Collection $activites;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
    }

    // ── Validation personnalisée pour la date de début ──
    #[Assert\Callback]
    public function validateDateDebut(ExecutionContextInterface $context): void
    {
        if ($this->DateDebut === null) {
            return;
        }

        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        // Contrôle 1 : La date ne peut pas être dans le passé
        if ($this->DateDebut < $today) {
            $context->buildViolation('Impossible de créer un événement avec une date de début dans le passé (la date de début ne peut pas être antérieure à aujourd\'hui).')
                ->atPath('DateDebut')
                ->addViolation();
            return;
        }
        
        // Contrôle 2 : Il faut au moins 5 jours de délai
        $minDate = clone $today;
        $minDate->modify('+5 days');
        
        if ($this->DateDebut < $minDate) {
            $context->buildViolation('La date de début doit être au moins 5 jours après aujourd\'hui (délai minimum de 5 jours avant l\'événement). Date minimale : ' . $minDate->format('d/m/Y'))
                ->atPath('DateDebut')
                ->addViolation();
        }
    }

    // ── Getters & Setters ─────────────────────────────────────────────────────

    public function getIDEv(): ?int { return $this->IDEv; }
    public function setIDEv(?int $IDEv): self { $this->IDEv = $IDEv; return $this; }

    public function getTitre(): ?string { return $this->Titre; }
    public function setTitre(?string $Titre): self { $this->Titre = $Titre; return $this; }

    public function getDescription(): ?string { return $this->Description; }
    public function setDescription(?string $Description): self { $this->Description = $Description; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->DateDebut; }
    public function setDateDebut(?\DateTimeInterface $DateDebut): self { $this->DateDebut = $DateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->DateFin; }
    public function setDateFin(?\DateTimeInterface $DateFin): self { $this->DateFin = $DateFin; return $this; }

    public function getLieu(): ?string { return $this->Lieu; }
    public function setLieu(?string $Lieu): self { $this->Lieu = $Lieu; return $this; }

    public function getCapaciteMax(): ?int { return $this->CapaciteMax; }
    public function setCapaciteMax(?int $CapaciteMax): self { $this->CapaciteMax = $CapaciteMax; return $this; }

    public function getImage(): ?string { return $this->Image; }
    public function setImage(?string $Image): self { $this->Image = $Image; return $this; }

    public function getOrganisateur(): ?string { return $this->Organisateur; }
    public function setOrganisateur(?string $Organisateur): self { $this->Organisateur = $Organisateur; return $this; }

    public function getUserId(): ?string { return $this->userId; }
    public function setUserId(?string $userId): self { $this->userId = $userId; return $this; }

    public function getActivites(): Collection { return $this->activites; }

    public function addActivite(Activite $activite): self
    {
        if (!$this->activites->contains($activite)) {
            $this->activites->add($activite);
            $activite->setEvenement($this);
        }
        return $this;
    }

    public function removeActivite(Activite $activite): self
    {
        if ($this->activites->removeElement($activite)) {
            if ($activite->getEvenement() === $this) {
                $activite->setEvenement(null);
            }
        }
        return $this;
    }
}