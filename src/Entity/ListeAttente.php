<?php

namespace App\Entity;

use App\Repository\ListeAttenteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
<<<<<<< HEAD
use Symfony\Component\Serializer\Annotation\Ignore;
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

#[ORM\Entity(repositoryClass: ListeAttenteRepository::class)]
#[ORM\Table(name: 'liste_attente')]
#[ORM\UniqueConstraint(name: 'unique_activite_utilisateur', columns: ['id_activite', 'email_utilisateur'])]
class ListeAttente
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_NOTIFIE = 'notifie';
    public const STATUT_CONFIRME = 'confirme';
    public const STATUT_EXPIRE = 'expire';
    public const STATUT_ANNULE = 'annule';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
<<<<<<< HEAD
    /** @phpstan-ignore-next-line */
    private int $id;
=======
    private ?int $id = null;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

    #[ORM\Column(name: 'id_activite', type: 'integer')]
    private ?int $idActivite = null;

    #[ORM\Column(name: 'email_utilisateur', length: 150)]
    private ?string $emailUtilisateur = null;

    #[ORM\Column(name: 'id_utilisateur', length: 50, nullable: true)]
    private ?string $idUtilisateur = null;

    #[ORM\Column(name: 'telephone_utilisateur', length: 20, nullable: true)]
    private ?string $telephoneUtilisateur = null;

    #[ORM\Column(name: 'nom_utilisateur', length: 100, nullable: true)]
    private ?string $nomUtilisateur = null;

    #[ORM\Column(name: 'prenom_utilisateur', length: 100, nullable: true)]
    private ?string $prenomUtilisateur = null;

    #[ORM\Column(name: 'date_inscription', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(name: 'date_notification', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNotification = null;

    #[ORM\Column(name: 'date_limite_confirmation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLimiteConfirmation = null;

    #[ORM\Column(name: 'date_confirmation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateConfirmation = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'en_attente'])]
<<<<<<< HEAD
    private string $statut = self::STATUT_EN_ATTENTE;

    /**
     * Token sensible — exclu de la sérialisation et des stack traces.
     */
    #[Ignore]
    #[ORM\Column(name: 'token_confirmation', length: 100, nullable: true)]
    private ?string $tokenConfirmation = null;

    public function getId(): ?int { return $this->id ?? null; }
=======
    private ?string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(name: 'token_confirmation', length: 100, nullable: true)]
    private ?string $tokenConfirmation = null;

    public function getId(): ?int { return $this->id; }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

    public function getIdActivite(): ?int { return $this->idActivite; }
    public function setIdActivite(int $idActivite): self { $this->idActivite = $idActivite; return $this; }

    public function getEmailUtilisateur(): ?string { return $this->emailUtilisateur; }
    public function setEmailUtilisateur(string $emailUtilisateur): self { $this->emailUtilisateur = $emailUtilisateur; return $this; }

    public function getIdUtilisateur(): ?string { return $this->idUtilisateur; }
    public function setIdUtilisateur(?string $idUtilisateur): self { $this->idUtilisateur = $idUtilisateur; return $this; }

    public function getTelephoneUtilisateur(): ?string { return $this->telephoneUtilisateur; }
    public function setTelephoneUtilisateur(?string $telephoneUtilisateur): self { $this->telephoneUtilisateur = $telephoneUtilisateur; return $this; }

    public function getNomUtilisateur(): ?string { return $this->nomUtilisateur; }
    public function setNomUtilisateur(?string $nomUtilisateur): self { $this->nomUtilisateur = $nomUtilisateur; return $this; }

    public function getPrenomUtilisateur(): ?string { return $this->prenomUtilisateur; }
    public function setPrenomUtilisateur(?string $prenomUtilisateur): self { $this->prenomUtilisateur = $prenomUtilisateur; return $this; }

    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $dateInscription): self { $this->dateInscription = $dateInscription; return $this; }

    public function getDateNotification(): ?\DateTimeInterface { return $this->dateNotification; }
    public function setDateNotification(?\DateTimeInterface $dateNotification): self { $this->dateNotification = $dateNotification; return $this; }

    public function getDateLimiteConfirmation(): ?\DateTimeInterface { return $this->dateLimiteConfirmation; }
    public function setDateLimiteConfirmation(?\DateTimeInterface $dateLimiteConfirmation): self { $this->dateLimiteConfirmation = $dateLimiteConfirmation; return $this; }

    public function getDateConfirmation(): ?\DateTimeInterface { return $this->dateConfirmation; }
    public function setDateConfirmation(?\DateTimeInterface $dateConfirmation): self { $this->dateConfirmation = $dateConfirmation; return $this; }

<<<<<<< HEAD
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    // Getter exclu de la sérialisation via #[Ignore] sur la propriété
    public function getTokenConfirmation(): ?string { return $this->tokenConfirmation; }

    public function setTokenConfirmation(#[\SensitiveParameter] ?string $tokenConfirmation): self
    {
        $this->tokenConfirmation = $tokenConfirmation;
        return $this;
    }
=======
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    public function getTokenConfirmation(): ?string { return $this->tokenConfirmation; }
    public function setTokenConfirmation(?string $tokenConfirmation): self { $this->tokenConfirmation = $tokenConfirmation; return $this; }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

    public function estEnAttente(): bool { return $this->statut === self::STATUT_EN_ATTENTE; }
    public function estNotifie(): bool { return $this->statut === self::STATUT_NOTIFIE; }
    public function estConfirme(): bool { return $this->statut === self::STATUT_CONFIRME; }
    public function estExpire(): bool { return $this->statut === self::STATUT_EXPIRE; }
    public function estAnnule(): bool { return $this->statut === self::STATUT_ANNULE; }

    public function estDelaiDepasse(): bool
    {
        if (!$this->dateLimiteConfirmation) return false;
        return new \DateTime() > $this->dateLimiteConfirmation;
    }
}