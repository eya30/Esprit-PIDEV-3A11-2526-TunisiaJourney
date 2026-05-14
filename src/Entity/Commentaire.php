<?php

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
    /** @phpstan-ignore-next-line */
    private ?int $idC = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private ?string $description = null;

    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'date_creation', type: 'date')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'idP', referencedColumnName: 'idP', nullable: false)]
    private ?Publication $publication = null;

    #[ORM\Column(name: 'id', type: 'integer', nullable: true)]
    private ?int $id = null;

    #[ORM\Column(name: 'tags', type: 'string', length: 255, nullable: true)]
    private ?string $tags = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
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
}