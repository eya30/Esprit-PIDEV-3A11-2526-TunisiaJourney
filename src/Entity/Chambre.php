<?php

namespace App\Entity;

use App\Repository\ChambreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChambreRepository::class)]
class Chambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $idCh = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le numéro de chambre est requis.")]
    #[Assert\Positive(message: "Le numéro de chambre doit être un nombre positif.")]
    private ?int $num = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: "Le type de chambre est requis.")]
    #[Assert\Length(
        min: 2,
        max: 80,
        minMessage: "Le type doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le type ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Choice(
        choices: ["simple", "double", "triple", "suite", "presidentielle", "familiale"],
        message: "Le type de chambre doit être : simple, double, triple, suite, presidentielle ou familiale."
    )]
    private ?string $type = null;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "Le prix par nuit est requis.")]
    #[Assert\Positive(message: "Le prix par nuit doit être un nombre positif.")]
    #[Assert\Range(
        min: 10,
        max: 2000,
        notInRangeMessage: "Le prix par nuit doit être compris entre {{ min }} et {{ max }} €."
    )]
    private ?float $prix_nuit = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ["disponible", "indisponible", "maintenance"],
        message: "Le status doit être : disponible, indisponible ou maintenance."
    )]
    private ?string $status = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La capacité maximale est requise.")]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: "La capacité maximale doit être comprise entre {{ min }} et {{ max }} personnes."
    )]
    private ?int $capacite_max = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Image(
        maxSize: "5M",
        mimeTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        mimeTypesMessage: "L'image doit être au format JPG, PNG, GIF ou WEBP.",
        maxSizeMessage: "L'image ne doit pas dépasser {{ maxSize }}."
    )]
    private ?string $image = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(
        message: "L'URL du modèle 3D doit être une URL valide."
    )]
    private ?string $modele3D_URL = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'idH', referencedColumnName: 'idH')]
    #[Assert\NotBlank(message: "L'hôtel associé est requis.")]
    private ?Hotel $hotel = null;

    public function getIdCh(): ?int
    {
        return $this->idCh;
    }

    public function getNum(): ?int
    {
        return $this->num;
    }

    public function setNum(int $num): static
    {
        $this->num = $num;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPrixNuit(): ?float
    {
        return $this->prix_nuit;
    }

    public function setPrixNuit(float $prix_nuit): static
    {
        $this->prix_nuit = $prix_nuit;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapaciteMax(int $capacite_max): static
    {
        $this->capacite_max = $capacite_max;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getModele3DURL(): ?string
    {
        return $this->modele3D_URL;
    }

    public function setModele3DURL(?string $modele3D_URL): static
    {
        $this->modele3D_URL = $modele3D_URL;
        return $this;
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): static
    {
        $this->hotel = $hotel;
        return $this;
    }
}
