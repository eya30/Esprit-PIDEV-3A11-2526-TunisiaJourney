<?php

namespace App\Entity;

use App\Repository\CodePromoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodePromoRepository::class)]
#[ORM\Table(name: 'code_promo')]
class CodePromo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idcode', type: 'integer')]
<<<<<<< HEAD
    /** @phpstan-ignore-next-line */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private ?int $idcode = null;

    #[ORM\Column(name: 'code', type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(name: 'description', type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'pourcentage_reduction', type: 'integer')]
    private int $pourcentageReduction;

    #[ORM\Column(name: 'date_debut', type: 'date')]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(name: 'date_fin', type: 'date')]
    private \DateTimeInterface $dateFin;

    #[ORM\Column(name: 'statut', type: 'string', length: 20, nullable: true, options: ['default' => 'actif'])]
    private ?string $statut = 'actif';

<<<<<<< HEAD
    public function getIdcode(): ?int { return $this->idcode; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPourcentageReduction(): int { return $this->pourcentageReduction; }
    public function setPourcentageReduction(int $pourcentageReduction): static { $this->pourcentageReduction = $pourcentageReduction; return $this; }

    public function getDateDebut(): \DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): \DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): static { $this->statut = $statut; return $this; }

=======
    // ── Getters / Setters ────────────────────────────────────────────────────

    public function getIdcode(): ?int
    {
        return $this->idcode;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getPourcentageReduction(): int
    {
        return $this->pourcentageReduction;
    }

    public function setPourcentageReduction(int $pourcentageReduction): static
    {
        $this->pourcentageReduction = $pourcentageReduction;
        return $this;
    }

    public function getDateDebut(): \DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): \DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * Vérifie si le code promo est valide à la date du jour :
     * - statut = 'actif'
     * - aujourd'hui entre date_debut et date_fin
     */
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function isValide(): bool
    {
        $today = new \DateTime('today');
        return $this->statut === 'actif'
            && $today >= $this->dateDebut
            && $today <= $this->dateFin;
    }

<<<<<<< HEAD
=======
    /**
     * Calcule le montant réduit à partir d'un prix de base.
     */
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function appliquer(float $prix): float
    {
        return $prix * (1 - $this->pourcentageReduction / 100);
    }
}