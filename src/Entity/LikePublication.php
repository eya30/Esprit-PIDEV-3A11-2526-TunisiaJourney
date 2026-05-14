<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'like_publication')]
class LikePublication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    /** @phpstan-ignore property.unusedType (Doctrine assigns via reflection) */
    private ?int $id = null;

    #[ORM\Column(name: 'type', type: 'string', length: 10)]
    private string $type = '';

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'likesCollection')]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'idP', nullable: false)]
    private ?Publication $publication = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId = 0;

    #[ORM\Column(name: 'date_action', type: 'datetime')]
    private \DateTimeInterface $dateAction;

    public function __construct()
    {
        $this->dateAction = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getPublication(): ?Publication { return $this->publication; }
    public function setPublication(?Publication $publication): self { $this->publication = $publication; return $this; }
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function getDateAction(): ?\DateTimeInterface { return $this->dateAction; }
    public function setDateAction(\DateTimeInterface $dateAction): self { $this->dateAction = $dateAction; return $this; }
}