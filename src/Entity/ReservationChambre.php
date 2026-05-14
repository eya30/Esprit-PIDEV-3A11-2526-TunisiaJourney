<?php

namespace App\Entity;

use App\Repository\ReservationChambreRepository;
<<<<<<< HEAD
use App\Entity\AvisChambre;
=======
use App\Entity\AvisChambre; 
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationChambreRepository::class)]
<<<<<<< HEAD
#[ORM\Table(name: 'reservation_chambre')]
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
class ReservationChambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
<<<<<<< HEAD
    #[ORM\Column(name: 'idRes', type: 'integer')]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(name: 'idUtilisateur', type: 'integer')]
=======
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "L'ID utilisateur est requis.")]
    #[Assert\Positive(message: "L'ID utilisateur doit être un nombre positif.")]
    private ?int $idUtilisateur = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'idCh', type: 'integer')]
=======
    #[ORM\Column]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "L'ID de la chambre est requis.")]
    #[Assert\Positive(message: "L'ID de la chambre doit être un nombre positif.")]
    private ?int $idCh = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'dateDebut', type: Types::DATE_MUTABLE)]
=======
    #[ORM\Column(type: Types::DATE_MUTABLE)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "La date de début est requise.")]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "La date de début ne peut pas être dans le passé."
    )]
    private ?\DateTime $dateDebut = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'dateFin', type: Types::DATE_MUTABLE)]
=======
    #[ORM\Column(type: Types::DATE_MUTABLE)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "La date de fin est requise.")]
    #[Assert\GreaterThan(
        propertyPath: "dateDebut",
        message: "La date de fin doit être postérieure à la date de début."
    )]
    private ?\DateTime $dateFin = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'nbNuit', type: 'integer')]
=======
    #[ORM\Column]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "Le nombre de nuits est requis.")]
    #[Assert\Positive(message: "Le nombre de nuits doit être un nombre positif.")]
    #[Assert\LessThanOrEqual(
        value: 90,
        message: "Le séjour ne peut pas dépasser {{ compared_value }} nuits."
    )]
    private ?int $nbNuit = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'prixTotal', type: Types::DECIMAL, precision: 10, scale: 2)]
=======
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "Le prix total est requis.")]
    #[Assert\Positive(message: "Le prix total doit être un nombre positif.")]
    #[Assert\LessThanOrEqual(
        value: 100000,
        message: "Le prix total ne peut pas dépasser {{ compared_value }} €."
    )]
    private ?string $prixTotal = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'nbPersonnes', type: 'integer')]
=======
    #[ORM\Column]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\NotBlank(message: "Le nombre de personnes est requis.")]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: "Le nombre de personnes doit être compris entre {{ min }} et {{ max }}."
    )]
    private ?int $nbPersonnes = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'detailsPrix', type: 'text', nullable: true)]
=======
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le détail du prix est requis.")]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\Length(
        max: 255,
        maxMessage: "Le détail du prix ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $detailsPrix = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'telephone', length: 20, nullable: true)]
=======
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est requis.")]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    #[ORM\Column(name: 'statut', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ["confirmé", "en_attente", "annulé", "terminé", "confirmee"],
=======
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut est requis.")]
    #[Assert\Choice(
        choices: ["confirmé", "en_attente", "annulé", "terminé"],
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        message: "Le statut doit être : confirmé, en_attente, annulé ou terminé."
    )]
    private ?string $statut = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'dateAnnulation', type: 'datetime', nullable: true)]
    private ?\DateTime $dateAnnulation = null;

    #[ORM\Column(name: 'montantRembourse', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: "Le montant remboursé doit être un nombre positif ou zéro.")]
    private ?string $montantRembourse = null;

    #[ORM\Column(name: 'nom', length: 100, nullable: true)]
=======
    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateAnnulation = null;

    #[ORM\Column(length: 100, nullable: true)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    #[ORM\Column(name: 'prenom', length: 100, nullable: true)]
=======
    #[ORM\Column(length: 100, nullable: true)]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    #[ORM\Column(name: 'email', length: 150, nullable: true)]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas une adresse email valide.")]
=======
    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Email(
        message: "L'email '{{ value }}' n'est pas une adresse email valide."
    )]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Assert\Length(
        max: 150,
        maxMessage: "L'email ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $email = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'avis_envoye', type: 'boolean', options: ['default' => false])]
    private bool $avisEnvoye = false;

    #[ORM\Column(name: 'token_avis', type: 'string', length: 100, nullable: true, unique: true)]
    private ?string $tokenAvis = null;

    #[ORM\OneToOne(mappedBy: 'reservationChambre', targetEntity: AvisChambre::class, cascade: ['persist', 'remove'])]
=======
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: "Le montant remboursé doit être un nombre positif ou zéro.")]
    #[Assert\LessThanOrEqual(
        propertyPath: "prixTotal",
        message: "Le montant remboursé ne peut pas dépasser le prix total."
    )]
    private ?string $montantRembourse = null;

    // ========== NOUVEAUX CHAMPS POUR LE SYSTÈME D'AVIS ==========

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $avisEnvoye = false;

    #[ORM\Column(type: 'string', length: 100, nullable: true, unique: true)]
    private ?string $tokenAvis = null;

    #[ORM\OneToOne(mappedBy: 'reservationChambre', cascade: ['persist', 'remove'])]
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private ?AvisChambre $avisChambre = null;

    // ========== GETTERS ET SETTERS ==========

<<<<<<< HEAD
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
=======
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->idUtilisateur;
    }

    public function setIdUtilisateur(int $idUtilisateur): static
    {
        $this->idUtilisateur = $idUtilisateur;
        return $this;
    }

    public function getIdCh(): ?int
    {
        return $this->idCh;
    }

    public function setIdCh(int $idCh): static
    {
        $this->idCh = $idCh;
        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getNbNuit(): ?int
    {
        return $this->nbNuit;
    }

    public function setNbNuit(int $nbNuit): static
    {
        $this->nbNuit = $nbNuit;
        return $this;
    }

    public function getPrixTotal(): ?string
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(string $prixTotal): static
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nbPersonnes;
    }

    public function setNbPersonnes(int $nbPersonnes): static
    {
        $this->nbPersonnes = $nbPersonnes;
        return $this;
    }

    public function getDetailsPrix(): ?string
    {
        return $this->detailsPrix;
    }

    public function setDetailsPrix(string $detailsPrix): static
    {
        $this->detailsPrix = $detailsPrix;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateAnnulation(): ?\DateTime
    {
        return $this->dateAnnulation;
    }

    public function setDateAnnulation(?\DateTime $dateAnnulation): static
    {
        $this->dateAnnulation = $dateAnnulation;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
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

    public function getMontantRembourse(): ?string
    {
        return $this->montantRembourse;
    }

    public function setMontantRembourse(?string $montantRembourse): static
    {
        $this->montantRembourse = $montantRembourse;
        return $this;
    }

    // ========== GETTERS ET SETTERS POUR LE SYSTÈME D'AVIS ==========

    public function isAvisEnvoye(): bool
    {
        return $this->avisEnvoye;
    }

    public function setAvisEnvoye(bool $avisEnvoye): static
    {
        $this->avisEnvoye = $avisEnvoye;
        return $this;
    }

    public function getTokenAvis(): ?string
    {
        return $this->tokenAvis;
    }

    public function setTokenAvis(?string $tokenAvis): static
    {
        $this->tokenAvis = $tokenAvis;
        return $this;
    }

    public function getAvisChambre(): ?AvisChambre
    {
        return $this->avisChambre;
    }

    public function setAvisChambre(?AvisChambre $avisChambre): static
    {
        $this->avisChambre = $avisChambre;
        return $this;
    }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
}