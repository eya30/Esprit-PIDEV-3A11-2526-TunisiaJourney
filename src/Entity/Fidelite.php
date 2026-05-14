<?php

namespace App\Entity;

// PAS de #[ORM\Entity] — cette classe est utilisée manuellement sans Doctrine ORM
class Fidelite
{
    private ?int $id = null;
    private ?int $idUtilisateur = null;
    private int $points = 0;
    private string $niveau = 'bronze';
    private string $totalDepense = '0.00';
    private ?\DateTime $dateDerniereActivite = null;
    private ?\DateTime $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): self { $this->idUtilisateur = $idUtilisateur; return $this; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): self { $this->points = $points; return $this; }
    public function getNiveau(): string { return $this->niveau; }
    public function setNiveau(string $niveau): self { $this->niveau = $niveau; return $this; }
    public function getTotalDepense(): string { return $this->totalDepense; }
    public function setTotalDepense(string $totalDepense): self { $this->totalDepense = $totalDepense; return $this; }
    public function getDateDerniereActivite(): ?\DateTime { return $this->dateDerniereActivite; }
    public function setDateDerniereActivite(?\DateTime $date): self { $this->dateDerniereActivite = $date; return $this; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): self { $this->createdAt = $createdAt; return $this; }
}