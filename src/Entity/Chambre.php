<?php

namespace App\Entity;

use App\Repository\ChambreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChambreRepository::class)]
<<<<<<< HEAD
#[ORM\Table(name: 'chambre')]
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
class Chambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
<<<<<<< HEAD
    #[ORM\Column(name: 'idCh', type: 'integer')]
    /** @phpstan-ignore-next-line */
    private ?int $idCh = null;

    #[ORM\Column(name: 'num', type: 'integer')]
=======
    #[ORM\Column]
    private ?int $idCh = null;

    #[ORM\Column]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "Le numéro de chambre est requis.")]
    #[Assert\Positive(message: "Le numéro de chambre doit être un nombre positif.")]
    private ?int $num = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'type', length: 80)]
=======
    #[ORM\Column(length: 80)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    #[ORM\Column(name: 'prix_nuit', type: 'float')]
=======
    #[ORM\Column(type: "float")]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "Le prix par nuit est requis.")]
    #[Assert\Positive(message: "Le prix par nuit doit être un nombre positif.")]
    #[Assert\Range(
        min: 10,
        max: 2000,
        notInRangeMessage: "Le prix par nuit doit être compris entre {{ min }} et {{ max }} €."
    )]
    private ?float $prix_nuit = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'status', length: 50, nullable: true)]
=======
    #[ORM\Column(length: 50, nullable: true)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\Choice(
        choices: ["disponible", "indisponible", "maintenance"],
        message: "Le status doit être : disponible, indisponible ou maintenance."
    )]
    private ?string $status = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'capacite_max', type: 'integer')]
=======
    #[ORM\Column]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "La capacité maximale est requise.")]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: "La capacité maximale doit être comprise entre {{ min }} et {{ max }} personnes."
    )]
    private ?int $capacite_max = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
=======
    #[ORM\Column(type: 'text', nullable: true)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'image', length: 255, nullable: true)]
=======
    #[ORM\Column(length: 255, nullable: true)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\Image(
        maxSize: "5M",
        mimeTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        mimeTypesMessage: "L'image doit être au format JPG, PNG, GIF ou WEBP.",
        maxSizeMessage: "L'image ne doit pas dépasser {{ maxSize }}."
    )]
    private ?string $image = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'modele3D_URL', length: 500, nullable: true)]
    #[Assert\Url(message: "L'URL du modèle 3D doit être une URL valide.")]
    private ?string $modele3D_URL = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
=======
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(
        message: "L'URL du modèle 3D doit être une URL valide."
    )]
    private ?string $modele3D_URL = null;

    #[ORM\ManyToOne]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[ORM\JoinColumn(name: 'idH', referencedColumnName: 'idH')]
    #[Assert\NotBlank(message: "L'hôtel associé est requis.")]
    private ?Hotel $hotel = null;

<<<<<<< HEAD
    public function getIdCh(): ?int { return $this->idCh; }

    public function getNum(): ?int { return $this->num; }
    public function setNum(int $num): static { $this->num = $num; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getPrixNuit(): ?float { return $this->prix_nuit; }
    public function setPrixNuit(float $prix_nuit): static { $this->prix_nuit = $prix_nuit; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }

    public function getCapaciteMax(): ?int { return $this->capacite_max; }
    public function setCapaciteMax(int $capacite_max): static { $this->capacite_max = $capacite_max; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getModele3DURL(): ?string { return $this->modele3D_URL; }
    public function setModele3DURL(?string $modele3D_URL): static { $this->modele3D_URL = $modele3D_URL; return $this; }

    public function getHotel(): ?Hotel { return $this->hotel; }
    public function setHotel(?Hotel $hotel): static { $this->hotel = $hotel; return $this; }
=======
    // Getters et Setters (inchangés)
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
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
}