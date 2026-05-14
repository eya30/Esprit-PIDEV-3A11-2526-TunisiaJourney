<?php
// src/Entity/Commentaire.php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'commentaire')]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idC', type: 'integer')]
    /** @phpstan-ignore property.unusedType (Doctrine assigns via reflection) */
    private ?int $idC = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description = '';

    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'date_creation', type: 'date')]
    private \DateTimeInterface $dateCreation;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'idP', nullable: false)]
    private ?Publication $publication = null;

    #[ORM\Column(name: 'id', type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(name: 'tags', type: 'string', length: 255, nullable: true)]
    private ?string $tags = null;

    // Fix ligne 38 : jamais assigné null → ?bool devient bool
    #[ORM\Column(name: 'is_cancelled', type: 'boolean', options: ['default' => false])]
    private bool $isCancelled = false;

    #[ORM\Column(name: 'cancelled_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: 'string', length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'original_description', type: 'text', nullable: true)]
    private ?string $originalDescription = null;

    #[ORM\Column(name: 'translated_lang', type: 'string', length: 5, nullable: true)]
    private ?string $translatedLang = null;

    #[ORM\Column(name: 'is_translated', type: 'boolean', options: ['default' => false])]
    private bool $isTranslated = false;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->createdAt    = new \DateTime();
        $this->isCancelled  = false;
        $this->isTranslated = false;
    }

    public function getIdC(): ?int { return $this->idC; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $d): self { $this->description = $d; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $i): self { $this->image = $i; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $d): self { $this->dateCreation = $d; return $this; }

    public function getPublication(): ?Publication { return $this->publication; }
    public function setPublication(?Publication $p): self { $this->publication = $p; return $this; }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }

    public function getTags(): ?string { return $this->tags; }
    public function setTags(?string $t): self { $this->tags = $t; return $this; }

    // Fix : getter retourne bool (plus ?bool)
    public function getIsCancelled(): bool { return $this->isCancelled; }
    public function setIsCancelled(bool $isCancelled): self { $this->isCancelled = $isCancelled; return $this; }

    public function getCancelledAt(): ?\DateTimeInterface { return $this->cancelledAt; }
    public function setCancelledAt(?\DateTimeInterface $cancelledAt): self { $this->cancelledAt = $cancelledAt; return $this; }

    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function setIpAddress(?string $ipAddress): self { $this->ipAddress = $ipAddress; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $userAgent): self { $this->userAgent = $userAgent; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt ?? new \DateTime(); return $this; }

    public function getOriginalDescription(): ?string { return $this->originalDescription; }
    public function setOriginalDescription(?string $desc): self { $this->originalDescription = $desc; return $this; }

    public function getTranslatedLang(): ?string { return $this->translatedLang; }
    public function setTranslatedLang(?string $lang): self { $this->translatedLang = $lang; return $this; }

    public function getIsTranslated(): bool { return $this->isTranslated; }
    public function setIsTranslated(bool $isTranslated): self { $this->isTranslated = $isTranslated; return $this; }
}