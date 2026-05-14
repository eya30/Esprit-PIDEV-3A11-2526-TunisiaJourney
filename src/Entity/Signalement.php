<?php

namespace App\Entity;

use App\Repository\SignalementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementRepository::class, readOnly: true)]

#[ORM\Table(name: 'signalement')]
class Signalement
{
    public const TYPE_VIDEO   = 'video';
    public const TYPE_IMAGE   = 'image';
    public const TYPE_COMMENT = 'comment';

    public const REASON_LANGAGE_INAPPROPRIE = 'langage_inapproprié';
    public const REASON_PROPOS_INJURIEUX    = 'propos_injurieux';
    public const REASON_DISCRIMINATION      = 'discrimination';
    public const REASON_SPAM                = 'spam';
    public const REASON_CONTENU_OFFENSANT   = 'contenu_offensant';
    public const REASON_AUTRE               = 'autre';

    public const REASON_LABELS = [
        'langage_inapproprié' => 'Langage inapproprié',
        'propos_injurieux'    => 'Propos injurieux',
        'discrimination'      => 'Discrimination',
        'spam'                => 'Spam ou publicité',
        'contenu_offensant'   => 'Contenu offensant',
        'autre'               => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    /** @phpstan-ignore property.onlyRead */
    private int $id;

    #[ORM\Column(name: 'type', type: 'string', length: 20)]
    private ?string $type = null;

    #[ORM\Column(name: 'target_id', type: 'integer')]
    private ?int $targetId = null;

    #[ORM\Column(name: 'reason', type: 'string', length: 50)]
    private ?string $reason = null;

    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'is_treated', type: 'boolean', options: ['default' => false])]
    private bool $isTreated = false;

    #[ORM\Column(name: 'treated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $treatedAt = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'snotification_id', type: 'integer', nullable: true)]
    private ?int $snotificationId = null;

    #[ORM\Column(name: 'user_reporter_id', type: 'integer', nullable: true)]
    private ?int $userReporterId = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->isTreated    = false;
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(int $targetId): self
    {
        $this->targetId = $targetId;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getReasonLabel(): string
    {
        return self::REASON_LABELS[$this->reason ?? ''] ?? ($this->reason ?? 'Motif inconnu');
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getIsTreated(): bool
    {
        return $this->isTreated;
    }

    public function setIsTreated(bool $isTreated): self
    {
        $this->isTreated = $isTreated;

        if ($isTreated && $this->treatedAt === null) {
            $this->treatedAt = new \DateTime();
        }

        return $this;
    }

    public function getTreatedAt(): ?\DateTimeInterface
    {
        return $this->treatedAt;
    }

    public function setTreatedAt(?\DateTimeInterface $treatedAt): self
    {
        $this->treatedAt = $treatedAt;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSnotificationId(): ?int
    {
        return $this->snotificationId;
    }

    public function setSnotificationId(?int $snotificationId): self
    {
        $this->snotificationId = $snotificationId;
        return $this;
    }

    public function getUserReporterId(): ?int
    {
        return $this->userReporterId;
    }

    public function setUserReporterId(?int $userReporterId): self
    {
        $this->userReporterId = $userReporterId;
        return $this;
    }
}
