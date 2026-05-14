<?php

namespace App\Entity;

use App\Repository\ReservationChambreRepository;
use App\Entity\AvisChambre;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationChambreRepository::class)]
#[ORM\Table(name: 'reservation_chambre')]
class ReservationChambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idRes', type: 'integer')]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(name: 'idUtilisateur', type: 'integer')]
    #[Assert\NotBlank(message: "L'ID utilisateur est requis.")]
    #[Assert\Positive(message: "L'ID utilisateur doit être un nombre positif.")]
    private ?int $idUtilisateur = null;

    #[ORM\Column(name: 'idCh', type: 'integer')]
    #[Assert\NotBlank(message: "L'ID de la chambre est requis.")]
    #[Assert\Positive(message: "L'ID de la chambre doit être un nombre positif.")]
    private ?int $idCh = null;

    #[ORM\Column(name: 'dateDebut', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est requise.")]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "La date de début ne peut pas être dans le passé."
    )]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(name: 'dateFin', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est requise.")]
    #[Assert\GreaterThan(
        propertyPath: "dateDebut",
        message: "La date de fin doit être postérieure à la date de début."
    )]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(name: 'nbNuit', type: 'integer')]
    #[Assert\NotBlank(message: "Le nombre de nuits est requis.")]
    #[Assert\Positive(message: "Le nombre de nuits doit être un nombre positif.")]
    #[Assert\LessThanOrEqual(
        value: 90,
        message: "Le séjour ne peut pas dépasser {{ compared_value }} nuits."
    )]
    private ?int $nbNuit = null;

    #[ORM\Column(name: 'prixTotal', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Le prix total est requis.")]
    #[Assert\Positive(message: "Le prix total doit être un nombre positif.")]
    #[Assert\LessThanOrEqual(
        value: 100000,
        message: "Le prix total ne peut pas dépasser {{ compared_value }} €."
    )]
    private ?string $prixTotal = null;

    #[ORM\Column(name: 'nbPersonnes', type: 'integer')]
    #[Assert\NotBlank(message: "Le nombre de personnes est requis.")]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: "Le nombre de personnes doit être compris entre {{ min }} et {{ max }}."
    )]
    private ?int $nbPersonnes = null;

    #[ORM\Column(name: 'detailsPrix', type: 'text', nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le détail du prix ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $detailsPrix = null;

    #[ORM\Column(name: 'telephone', length: 20, nullable: true)]
    #[Assert\Length(
        min: 8,
        max: 20,
        minMessage: "Le numéro de téléphone doit contenir au moins {{ limit }} chiffres.",
        maxMessage: "Le numéro de téléphone ne peut pas dépasser {{ limit }} chiffres."
    )]
    #[Assert\Regex(
        pattern: "/^[0-9+\-\s]+$/",
        message: "Le numéro de téléphone ne doit contenir que des chiffres, espaces, tirets ou le signe +."
    )]
    private ?string $telephone = null;

    #[ORM\Column(name: 'statut', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ["confirmé", "en_attente", "annulé", "terminé", "confirmee"],
        message: "Le statut doit être : confirmé, en_attente, annulé ou terminé."
    )]
    private ?string $statut = null;

    #[ORM\Column(name: 'dateAnnulation', type: 'datetime', nullable: true)]
    private ?\DateTime $dateAnnulation = null;

    #[ORM\Column(name: 'montantRembourse', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: "Le montant remboursé doit être un nombre positif ou zéro.")]
    private ?string $montantRembourse = null;

    #[ORM\Column(name: 'nom', length: 100, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\'-]+$/",
        message: "Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets."
    )]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom', length: 100, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le prénom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le prénom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\'-]+$/",
        message: "Le prénom ne doit contenir que des lettres, espaces, apostrophes ou tirets."
    )]
    private ?string $prenom = null;

    #[ORM\Column(name: 'email', length: 150, nullable: true)]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas une adresse email valide.")]
    #[Assert\Length(
        max: 150,
        maxMessage: "L'email ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $email = null;

    #[ORM\Column(name: 'avis_envoye', type: 'boolean', options: ['default' => false])]
    private bool $avisEnvoye = false;

    #[ORM\Column(name: 'token_avis', type: 'string', length: 100, nullable: true, unique: true)]
    private ?string $tokenAvis = null;

    #[ORM\OneToOne(mappedBy: 'reservationChambre', targetEntity: AvisChambre::class, cascade: ['persist', 'remove'])]
    private ?AvisChambre $avisChambre = null;

    // ========== GETTERS ET SETTERS ==========

    public function getId(): ?int { return $this->id; }

    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): static { $this->idUtilisateur = $idUtilisateur; return $this; }

    public function getIdCh(): ?int { return $this->idCh; }
    public function setIdCh(int $idCh): static { $this->idCh = $idCh; return $this; }

    public function getDateDebut(): ?\DateTime { return $this->dateDebut; }
    public function setDateDebut(\DateTime $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTime { return $this->dateFin; }
    public function setDateFin(\DateTime $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getNbNuit(): ?int { return $this->nbNuit; }
    public function setNbNuit(int $nbNuit): static { $this->nbNuit = $nbNuit; return $this; }

    public function getPrixTotal(): ?string { return $this->prixTotal; }
    public function setPrixTotal(string $prixTotal): static { $this->prixTotal = $prixTotal; return $this; }

    public function getNbPersonnes(): ?int { return $this->nbPersonnes; }
    public function setNbPersonnes(int $nbPersonnes): static { $this->nbPersonnes = $nbPersonnes; return $this; }

    public function getDetailsPrix(): ?string { return $this->detailsPrix; }
    public function setDetailsPrix(?string $detailsPrix): static { $this->detailsPrix = $detailsPrix; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): static { $this->statut = $statut; return $this; }

    public function getDateAnnulation(): ?\DateTime { return $this->dateAnnulation; }
    public function setDateAnnulation(?\DateTime $dateAnnulation): static { $this->dateAnnulation = $dateAnnulation; return $this; }

    public function getMontantRembourse(): ?string { return $this->montantRembourse; }
    public function setMontantRembourse(?string $montantRembourse): static { $this->montantRembourse = $montantRembourse; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function isAvisEnvoye(): bool { return $this->avisEnvoye; }
    public function setAvisEnvoye(bool $avisEnvoye): static { $this->avisEnvoye = $avisEnvoye; return $this; }

    public function getTokenAvis(): ?string { return $this->tokenAvis; }
    public function setTokenAvis(?string $tokenAvis): static { $this->tokenAvis = $tokenAvis; return $this; }

    public function getAvisChambre(): ?AvisChambre { return $this->avisChambre; }
    public function setAvisChambre(?AvisChambre $avisChambre): static { $this->avisChambre = $avisChambre; return $this; }
}