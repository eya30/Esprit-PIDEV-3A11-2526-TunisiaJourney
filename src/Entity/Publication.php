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
    /** @phpstan-ignore property.onlyRead */
    private int $idP;

    #[ORM\Column(name: 'Description', type: 'string', length: 200, nullable: true)]
    #[Assert\Length(max: 200, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'video', type: 'string', length: 500, nullable: true)]
    private ?string $video = null;

    #[ORM\Column(name: 'date_creation', type: 'date')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: Forum::class, inversedBy: 'publications')]
    #[ORM\JoinColumn(
        name: 'id_f',
        referencedColumnName: 'id_f',  // ← CORRIGÉ : id_f au lieu de idF
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Forum $forum = null;

    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    /** @var Collection<int, LikePublication> */
    #[ORM\OneToMany(
        mappedBy: 'publication',
        targetEntity: LikePublication::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $likesCollection;

    /** @var Collection<int, Commentaire> */
    #[ORM\OneToMany(
        mappedBy: 'publication',
        targetEntity: Commentaire::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        fetch: 'EAGER'
    )]
    private Collection $commentaires;

    #[ORM\Column(name: 'vues', type: 'integer', options: ['default' => 0])]
    private int $vues = 0;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->likesCollection = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->vues = 0;
    }

    public function getIdP(): ?int { return $this->idP ?? null; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): self { $this->description = $d; return $this; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $n): self { $this->nom = $n; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $i): self { $this->image = $i; return $this; }
    public function getVideo(): ?string { return $this->video; }
    public function setVideo(?string $v): self { $this->video = $v; return $this; }
    public function getVues(): int { return $this->vues; }
    public function setVues(int $vues): self { $this->vues = $vues; return $this; }
    public function isYoutubeVideo(): bool
    {
        return $this->video !== null && (bool) preg_match('/(youtube\.com|youtu\.be)/i', $this->video);
    }
    public function isVimeoVideo(): bool
    {
        return $this->video !== null && str_contains(strtolower($this->video), 'vimeo.com');
    }
    public function getYoutubeId(): ?string
    {
        if (!$this->video) return null;
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->video, $m);
        return $m[1] ?? null;
    }
    public function getVimeoId(): ?string
    {
        if (!$this->video) return null;
        preg_match('/vimeo\.com\/(\d+)/', $this->video, $m);
        return $m[1] ?? null;
    }
    public function hasMedia(): bool
    {
        return $this->image !== null || $this->video !== null;
    }
    public function getMediaType(): string
    {
        if ($this->video) return 'video';
        if ($this->image) return 'image';
        return 'none';
    }
    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $d): self { $this->dateCreation = $d; return $this; }
    public function getForum(): ?Forum { return $this->forum; }
    public function setForum(?Forum $f): self { $this->forum = $f; return $this; }
    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
    public function getLikesCollection(): Collection { return $this->likesCollection; }
    public function countLikes(): int
    {
        return $this->likesCollection->filter(fn(LikePublication $l) => $l->getType() === 'like')->count();
    }
    public function countDislikes(): int
    {
        return $this->likesCollection->filter(fn(LikePublication $l) => $l->getType() === 'dislike')->count();
    }
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