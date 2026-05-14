<?php
 
namespace App\Entity;
 
use App\Repository\ReservationProgRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
 
#[ORM\Entity(repositoryClass: ReservationProgRepository::class)]
#[ORM\Table(name: 'reservationprog')]
class ReservationProg
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idRP", type: "integer")]
    /** @phpstan-ignore-next-line */
    private ?int $idRP = null;
 
    #[ORM\Column(name: "nom", length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Le nom doit contenir au moins 2 caractères', maxMessage: 'Le nom ne peut pas dépasser 50 caractères')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s-]+$/', message: 'Le nom ne doit contenir que des lettres, espaces ou tirets')]
    private ?string $nom = null;
 
    #[ORM\Column(name: "prenom", length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Le prénom doit contenir au moins 2 caractères', maxMessage: 'Le prénom ne peut pas dépasser 50 caractères')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s-]+$/', message: 'Le prénom ne doit contenir que des lettres, espaces ou tirets')]
    private ?string $prenom = null;
 
    #[ORM\Column(name: "telephone", length: 20)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    #[Assert\Length(min: 8, max: 8, exactMessage: 'Le téléphone doit contenir exactement 8 chiffres')]
    #[Assert\Regex(pattern: '/^[0-9]+$/', message: 'Le téléphone ne doit contenir que des chiffres')]
    private ?string $telephone = null;
 
    #[ORM\Column(name: "nbre", type: "integer")]
    #[Assert\NotBlank(message: 'Le nombre de personnes est obligatoire')]
    #[Assert\Positive(message: 'Le nombre de personnes doit être supérieur à 0')]
    #[Assert\LessThanOrEqual(value: 20, message: 'Le nombre de personnes ne peut pas dépasser 20')]
    private ?int $nbre = null;

    // FIX : type float au lieu de Types::DECIMAL pour correspondre à la propriété PHP float
    #[ORM\Column(name: "prixProg", type: "float", nullable: true)]
    private ?float $prixProg = null;
 
    #[ORM\Column(name: "idP", type: "string", length: 50)]
    #[Assert\NotBlank(message: 'Le programme est obligatoire')]
    private ?string $idP = null;
 
    #[ORM\Column(name: "dateProgramme", type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateProgramme = null;
 
    #[ORM\Column(name: "email", length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: 'Veuillez saisir un email valide')]
    private ?string $email = null;
 
    #[ORM\Column(name: "statutPaiement", length: 50, nullable: true)]
    private ?string $statutPaiement = null;
 
    #[ORM\Column(name: "stripeSessionId", length: 255, nullable: true)]
    private ?string $stripeSessionId = null;
 
    #[ORM\Column(name: "user_id", type: "integer", nullable: true)]
    private ?int $userId = null;

    public function getIdRP(): ?int { return $this->idRP; }
 
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = trim($nom); return $this; }
 
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = trim($prenom); return $this; }
 
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(string $telephone): static { $this->telephone = trim($telephone); return $this; }
 
    public function getNbre(): ?int { return $this->nbre; }
    public function setNbre(int $nbre): static { $this->nbre = $nbre; return $this; }
 
    public function getPrixProg(): ?float { return $this->prixProg; }
    public function setPrixProg(?float $prixProg): static { $this->prixProg = $prixProg; return $this; }
 
    public function getIdP(): ?string { return $this->idP; }
    public function setIdP(string $idP): static { $this->idP = $idP; return $this; }
 
    public function getDateProgramme(): ?\DateTimeInterface { return $this->dateProgramme; }
    public function setDateProgramme(\DateTimeInterface $dateProgramme): static { $this->dateProgramme = $dateProgramme; return $this; }
 
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = trim($email); return $this; }
 
    public function getStatutPaiement(): ?string { return $this->statutPaiement; }
    public function setStatutPaiement(?string $statutPaiement): static { $this->statutPaiement = $statutPaiement; return $this; }
 
    public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
    public function setStripeSessionId(?string $stripeSessionId): static { $this->stripeSessionId = $stripeSessionId; return $this; }
 
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): static { $this->userId = $userId; return $this; }
}