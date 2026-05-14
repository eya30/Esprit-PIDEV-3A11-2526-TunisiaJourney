<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'publication')]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idP', type: 'integer')]
    /** @phpstan-ignore-next-line */
    private ?int $idP = null;

    #[ORM\Column(name: 'Description', type: 'string', length: 200, nullable: true)]
    #[Assert\Length(max: 200, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'date_creation', type: 'date')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: Forum::class, inversedBy: 'publications')]
    #[ORM\JoinColumn(name: 'idF', referencedColumnName: 'idF', nullable: false)]
    private ?Forum $forum = null;

    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    /** @var Collection<int, LikePublication> */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: LikePublication::class, cascade: ['persist', 'remove'])]
    private Collection $likesCollection;

    /** @var Collection<int, Commentaire> */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: Commentaire::class, cascade: ['persist', 'remove'])]
    private Collection $commentaires;

    #[ORM\Column(name: 'vues', type: 'integer', options: ['default' => 0])]
    private int $vues = 0;

    public function __construct()
    {
        $this->commentaires    = new ArrayCollection();
        $this->likesCollection = new ArrayCollection();
        $this->dateCreation    = new \DateTime();
        $this->vues            = 0;
    }

    public function getIdP(): ?int { return $this->idP; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): self { $this->description = $d; return $this; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $n): self { $this->nom = $n; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $i): self { $this->image = $i; return $this; }
    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $d): self { $this->dateCreation = $d; return $this; }
    public function getForum(): ?Forum { return $this->forum; }
    public function setForum(?Forum $f): self { $this->forum = $f; return $this; }
    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
    public function getVues(): int { return $this->vues; }
    public function setVues(int $vues): self { $this->vues = $vues; return $this; }

    /** @return Collection<int, LikePublication> */
    public function getLikesCollection(): Collection { return $this->likesCollection; }
    public function countLikes(): int { return $this->likesCollection->filter(fn(LikePublication $l) => $l->getType() === 'like')->count(); }
    public function countDislikes(): int { return $this->likesCollection->filter(fn(LikePublication $l) => $l->getType() === 'dislike')->count(); }

    /** @return Collection<int, Commentaire> */
    public function getCommentaires(): Collection { return $this->commentaires; }

    public function addCommentaire(Commentaire $c): self
    {
        if (!$this->commentaires->contains($c)) {
            $this->commentaires[] = $c;
            $c->setPublication($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $c): self
    {
        if ($this->commentaires->removeElement($c) && $c->getPublication() === $this) {
            $c->setPublication(null);
        }
        return $this;
    }
}