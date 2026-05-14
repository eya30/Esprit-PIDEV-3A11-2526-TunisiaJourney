<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\AdminLogRepository::class)]
#[ORM\Table(name: "admin_log")]
#[ORM\Index(columns: ["created_at"], name: "idx_log_date")]
#[ORM\Index(columns: ["action"],     name: "idx_log_action")]
class AdminLog
{
    // ── Actions backoffice ──────────────────────────────────────
    public const ACTION_EDIT_USER      = 'EDIT_USER';
    public const ACTION_DELETE_USER    = 'DELETE_USER';
    public const ACTION_TOGGLE_STATUT  = 'TOGGLE_STATUT';
    public const ACTION_UPLOAD_PHOTO_ADMIN = 'UPLOAD_PHOTO_ADMIN';

    // ── Actions front ───────────────────────────────────────────
    public const ACTION_EDIT_PROFILE   = 'EDIT_PROFILE';
    public const ACTION_CHANGE_PASSWORD = 'CHANGE_PASSWORD';
    public const ACTION_UPLOAD_PHOTO   = 'UPLOAD_PHOTO';
    public const ACTION_REGISTER       = 'REGISTER';
    public const ACTION_LOGIN          = 'LOGIN';
    public const ACTION_LOGIN_GOOGLE   = 'LOGIN_GOOGLE';
    public const ACTION_LOGIN_FACE     = 'LOGIN_FACE';
    public const ACTION_LOGOUT         = 'LOGOUT';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
   /** @phpstan-ignore-next-line */
    private int|null $id = null;

    // L'acteur (peut être null si compte supprimé)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $acteur = null;

    // Nom de l'acteur figé au moment de l'action (même si compte supprimé plus tard)
    #[ORM\Column(type: "string", length: 255)]
    private string $acteurNom = '';

    #[ORM\Column(type: "string", length: 50)]
    private string $acteurRole = '';

    // L'action effectuée
    #[ORM\Column(type: "string", length: 50)]
    private string $action = '';

    // Sur qui / quoi (ex: "eyaa bogh (id=19)" ou "soi-même")
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $cible = null;

    // Détails des changements (ex: "nom: bogh→smith | statut: ACTIF→BLOQUE")
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ── Getters / Setters ────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getActeur(): ?User { return $this->acteur; }
    public function setActeur(?User $acteur): self { $this->acteur = $acteur; return $this; }

    public function getActeurNom(): string { return $this->acteurNom; }
    public function setActeurNom(string $v): self { $this->acteurNom = $v; return $this; }

    public function getActeurRole(): string { return $this->acteurRole; }
    public function setActeurRole(string $v): self { $this->acteurRole = $v; return $this; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $v): self { $this->action = $v; return $this; }

    public function getCible(): ?string { return $this->cible; }
    public function setCible(?string $v): self { $this->cible = $v; return $this; }

    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $v): self { $this->details = $v; return $this; }

    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $v): self { $this->ip = $v; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $v): self { $this->createdAt = $v; return $this; }

    // ── Helper : libellé lisible de l'action ────────────────────
    public function getActionLabel(): string
    {
        return match($this->action) {
            self::ACTION_EDIT_USER          => 'Modification utilisateur',
            self::ACTION_DELETE_USER        => 'Suppression utilisateur',
            self::ACTION_TOGGLE_STATUT      => 'Blocage / Déblocage',
            self::ACTION_UPLOAD_PHOTO_ADMIN => 'Photo modifiée (admin)',
            self::ACTION_EDIT_PROFILE       => 'Profil modifié',
            self::ACTION_CHANGE_PASSWORD    => 'Mot de passe changé',
            self::ACTION_UPLOAD_PHOTO       => 'Photo de profil modifiée',
            self::ACTION_REGISTER           => 'Inscription',
            self::ACTION_LOGIN              => 'Connexion',
            self::ACTION_LOGIN_GOOGLE       => 'Connexion Google',
            self::ACTION_LOGIN_FACE         => 'Connexion Face ID',
            self::ACTION_LOGOUT             => 'Déconnexion',
            default                         => $this->action,
        };
    }

    // ── Helper : icône Font Awesome ──────────────────────────────
    public function getActionIcon(): string
    {
        return match($this->action) {
            self::ACTION_EDIT_USER, self::ACTION_EDIT_PROFILE => 'fa-pen',
            self::ACTION_DELETE_USER        => 'fa-trash-alt',
            self::ACTION_TOGGLE_STATUT      => 'fa-ban',
            self::ACTION_UPLOAD_PHOTO, self::ACTION_UPLOAD_PHOTO_ADMIN => 'fa-image',
            self::ACTION_CHANGE_PASSWORD    => 'fa-key',
            self::ACTION_REGISTER           => 'fa-user-plus',
            self::ACTION_LOGIN              => 'fa-sign-in-alt',
            self::ACTION_LOGIN_GOOGLE       => 'fa-google',
            self::ACTION_LOGIN_FACE         => 'fa-face-smile',
            self::ACTION_LOGOUT             => 'fa-sign-out-alt',
            default                         => 'fa-circle-info',
        };
    }

    // ── Helper : couleur badge ───────────────────────────────────
    public function getActionColor(): string
    {
        return match($this->action) {
            self::ACTION_DELETE_USER        => 'danger',
            self::ACTION_TOGGLE_STATUT      => 'warning',
            self::ACTION_LOGIN, self::ACTION_LOGIN_GOOGLE, self::ACTION_LOGIN_FACE => 'success',
            self::ACTION_REGISTER           => 'info',
            self::ACTION_LOGOUT             => 'secondary',
            default                         => 'primary',
        };
    }
}
