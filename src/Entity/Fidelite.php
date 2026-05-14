<?php

namespace App\Entity;

<<<<<<< HEAD
// PAS de #[ORM\Entity] — cette classe est utilisée manuellement sans Doctrine ORM
class Fidelite
{
    private ?int $id = null;
    private ?int $idUtilisateur = null;
    private int $points = 0;
    private string $niveau = 'bronze';
    private string $totalDepense = '0.00';
    private ?\DateTime $dateDerniereActivite = null;
=======
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'fidelite')]
class Fidelite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $idUtilisateur = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private ?int $points = 0;

    #[ORM\Column(length: 20, options: ['default' => 'bronze'])]
    private ?string $niveau = 'bronze';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    private ?string $totalDepense = '0.00';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dateDerniereActivite = null;

    #[ORM\Column(type: 'datetime')]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private ?\DateTime $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): self { $this->idUtilisateur = $idUtilisateur; return $this; }
<<<<<<< HEAD
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): self { $this->points = $points; return $this; }
    public function getNiveau(): string { return $this->niveau; }
    public function setNiveau(string $niveau): self { $this->niveau = $niveau; return $this; }
    public function getTotalDepense(): string { return $this->totalDepense; }
=======
    public function getPoints(): ?int { return $this->points; }
    public function setPoints(int $points): self { $this->points = $points; return $this; }
    public function getNiveau(): ?string { return $this->niveau; }
    public function setNiveau(string $niveau): self { $this->niveau = $niveau; return $this; }
    public function getTotalDepense(): ?string { return $this->totalDepense; }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function setTotalDepense(string $totalDepense): self { $this->totalDepense = $totalDepense; return $this; }
    public function getDateDerniereActivite(): ?\DateTime { return $this->dateDerniereActivite; }
    public function setDateDerniereActivite(?\DateTime $date): self { $this->dateDerniereActivite = $date; return $this; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): self { $this->createdAt = $createdAt; return $this; }
}