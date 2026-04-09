<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\Table(name: "utilisateur")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUT_ACTIF       = 'ACTIF';
    public const STATUT_BLOQUE      = 'BLOQUE';
    public const NIVEAU_ADMIN       = 'ADMIN';
    public const NIVEAU_SUPER_ADMIN = 'SUPER_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // ── Nom ──────────────────────────────────────────────────────
    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(max: 100, maxMessage: "100 caractères maximum.")]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-\']+$/',
        message: "Le nom ne doit contenir que des lettres."
    )]
    private ?string $nom = null;

    // ── Prénom ───────────────────────────────────────────────────
    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(max: 100, maxMessage: "100 caractères maximum.")]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-\']+$/',
        message: "Le prénom ne doit contenir que des lettres."
    )]
    private ?string $prenom = null;

    // ── Email ────────────────────────────────────────────────────
    #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "Adresse email invalide.")]
    private ?string $email = null;

    // ── Mot de passe ─────────────────────────────────────────────
    // Pas de Assert ici : hashé manuellement, validé dans RegistrationType
    // et dans le contrôleur pour le profil.
    #[ORM\Column(type: "string", length: 255)]
    private ?string $motDePasse = null;

    // ── Téléphone ────────────────────────────────────────────────
    // Obligatoire pour l'edit profil ; nullable en BDD car non requis à l'inscription
    #[ORM\Column(type: "string", length: 30, nullable: true)]
    #[Assert\NotBlank(message: "Le téléphone est obligatoire.")]
    #[Assert\Regex(
        pattern: '/^\d{8,15}$/',
        message: "Le téléphone doit contenir uniquement des chiffres (8 à 15 chiffres)."
    )]
    private ?string $telephone = null;

    // ── Date de naissance ────────────────────────────────────────
    // Obligatoire pour l'edit profil ; nullable en BDD car non requis à l'inscription
    #[ORM\Column(type: "date", nullable: true)]
    #[Assert\NotBlank(message: "La date de naissance est obligatoire.")]
    #[Assert\LessThanOrEqual(
        value: "-18 years",
        message: "Vous devez avoir au moins 18 ans."
    )]
    private ?\DateTimeInterface $dateNaissance = null;

    // ── Adresse ──────────────────────────────────────────────────
    // Obligatoire pour l'edit profil ; nullable en BDD car non requis à l'inscription
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "255 caractères maximum.")]
    private ?string $adresse = null;

    #[ORM\Column(type: "string", length: 20)]
    private ?string $role = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
private ?string $statut = null;



    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $profileImageUrl = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $faceToken = null;

    // ================= GETTERS / SETTERS =================

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getMotDePasse(): ?string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): self { $this->motDePasse = $motDePasse; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self { $this->dateNaissance = $dateNaissance; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self { $this->adresse = $adresse; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $dateInscription): self { $this->dateInscription = $dateInscription; return $this; }

    public function getStatut(): ?string { return $this->statut; }
   public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }

    public function getNiveau(): ?string { return $this->niveau; }
    public function setNiveau(?string $niveau): self { $this->niveau = $niveau; return $this; }

    public function getProfileImageUrl(): ?string { return $this->profileImageUrl; }
    public function setProfileImageUrl(?string $profileImageUrl): self { $this->profileImageUrl = $profileImageUrl; return $this; }

    public function getFaceToken(): ?string { return $this->faceToken; }
    public function setFaceToken(?string $faceToken): self { $this->faceToken = $faceToken; return $this; }

    public function getPassword(): ?string { return $this->motDePasse; }

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        return match($this->niveau) {
            'ADMIN'       => ['ROLE_ADMIN',       'ROLE_USER'],
            'SUPER_ADMIN' => ['ROLE_SUPER_ADMIN', 'ROLE_USER'],
            default       => ['ROLE_MEMBRE',      'ROLE_USER'],
        };
    }

    public function eraseCredentials(): void {}
}