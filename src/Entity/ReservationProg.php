<?php

namespace App\Entity;

use App\Repository\ReservationProgRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationProgRepository::class)]
#[ORM\Table(name: 'reservationprog')]
class ReservationProg
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idRP", type: "integer")]
    private ?int $idRP = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column]
    private ?int $nbre = null;

    #[ORM\Column(name: "prixProg", type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?float $prixProg = null;

    #[ORM\Column(name: "idP", type: "string", length: 50)]
    private ?string $idP = null;

    #[ORM\Column(name: "dateProgramme", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateProgramme = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statutPaiement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(name: "user_id", type: "integer", nullable: true)]
    private ?int $userId = null;

    public function getIdRP(): ?int
    {
        return $this->idRP;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getNbre(): ?int
    {
        return $this->nbre;
    }

    public function setNbre(int $nbre): static
    {
        $this->nbre = $nbre;
        return $this;
    }

    public function getPrixProg(): ?float
    {
        return $this->prixProg;
    }

    public function setPrixProg(float $prixProg): static
    {
        $this->prixProg = $prixProg;
        return $this;
    }

    public function getIdP(): ?string
    {
        return $this->idP;
    }

    public function setIdP(string $idP): static
    {
        $this->idP = $idP;
        return $this;
    }

    public function getDateProgramme(): ?\DateTimeInterface
    {
        return $this->dateProgramme;
    }

    public function setDateProgramme(\DateTimeInterface $dateProgramme): static
    {
        $this->dateProgramme = $dateProgramme;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getStatutPaiement(): ?string
    {
        return $this->statutPaiement;
    }

    public function setStatutPaiement(?string $statutPaiement): static
    {
        $this->statutPaiement = $statutPaiement;
        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }
}