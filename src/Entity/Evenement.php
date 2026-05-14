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
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "IDEv", type: "integer")]
    /** @phpstan-ignore-next-line */
    private ?int $IDEv = null;

    #[ORM\Column(name: "Titre", length: 50)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 8, minMessage: "Le titre doit contenir au moins {{ limit }} caractères.")]
    private ?string $Titre = null;

    #[ORM\Column(name: "description", length: 200)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 15, minMessage: "La description doit contenir au moins {{ limit }} caractères.")]
    private ?string $Description = null;

    #[ORM\Column(name: "DateDebut", type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $DateDebut = null;

    #[ORM\Column(name: "DateFin", type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $DateFin = null;

    #[ORM\Column(name: "Lieu", length: 100)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    private ?string $Lieu = null;

    #[ORM\Column(name: "CapaciteMax", type: "integer", nullable: true)]
    private ?int $CapaciteMax = null;

    #[ORM\Column(name: "image", length: 200, nullable: true)]
    private ?string $Image = null;

    #[ORM\Column(name: "organisateur", length: 50, nullable: true)]
    private ?string $Organisateur = null;

    #[ORM\Column(name: "id", type: "string", length: 50)]
    private ?string $userId = null;

    /**
     * @var Collection<int, Activite>
     */
    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Activite::class, cascade: ['persist', 'remove'])]
    private Collection $activites;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
    }

    public function getIDEv(): ?int { return $this->IDEv; }
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