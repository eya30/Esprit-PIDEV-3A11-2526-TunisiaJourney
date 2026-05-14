<?php

namespace App\Entity;

use App\Repository\ProgrammeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProgrammeRepository::class)]
#[ORM\Table(name: 'programmes')]
class Programme
{
    #[ORM\Id]
    #[ORM\Column(name: "idProg", type: "string", length: 50)]
    private string $idProg = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du programme est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^[^0-9]+$/u',
        message: 'Le nom ne doit pas contenir de chiffres.'
    )]
    private string $nom = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    private string $description = '';

    #[ORM\Column(name: "dateDebut", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(name: "dateFin", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    private \DateTimeInterface $dateFin;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire.')]
    private string $lieu = '';

    #[ORM\Column(name: "activiteAssociee", length: 255)]
    #[Assert\NotBlank(message: 'L\'activité associée est obligatoire.')]
    private string $activiteAssociee = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'hôtel est obligatoire.')]
    private string $hotel = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "voyage_id", referencedColumnName: "idV", nullable: true)]
    private ?Voyage $voyage = null;

    public function __construct()
    {
        $this->dateDebut = new \DateTime();
        $this->dateFin   = new \DateTime();
    }

    public function getIdProg(): ?string
    {
        return $this->idProg;
    }

    public function setIdProg(string $idProg): static
    {
        $this->idProg = $idProg;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getActiviteAssociee(): ?string
    {
        return $this->activiteAssociee;
    }

    public function setActiviteAssociee(string $activiteAssociee): static
    {
        $this->activiteAssociee = $activiteAssociee;
        return $this;
    }

    public function getHotel(): ?string
    {
        return $this->hotel;
    }

    public function setHotel(string $hotel): static
    {
        $this->hotel = $hotel;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): static
    {
        $this->voyage = $voyage;
        return $this;
    }
}