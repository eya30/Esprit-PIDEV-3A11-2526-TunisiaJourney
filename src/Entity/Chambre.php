<?php

namespace App\Entity;

use App\Repository\ChambreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChambreRepository::class)]
class Chambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $idCh = null;

    #[ORM\Column]
    private ?int $num = null;

    #[ORM\Column(length: 80)]
    private ?string $type = null;

    #[ORM\Column(type: "float")]
    private ?float $prix_nuit = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column]
    private ?int $capacite_max = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $modele3D_URL = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'idH', referencedColumnName: 'idH')]
    private ?Hotel $hotel = null;

    // Getters et Setters
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