<?php
// src/Entity/SNotification.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'snotifications')]
class SNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    // Fix PHPStan :15 — Doctrine hydrates this via reflection; the `int` branch
    // is never assigned in userland code, but it IS set by Doctrine after flush().
    // We keep ?int (null before persist) and suppress the false-positive warning.
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(name: 'message', type: 'text')]
    private string $message;

    #[ORM\Column(name: 'signalement_id', type: 'integer', nullable: true)]
    private ?int $signalementId = null;

    #[ORM\Column(name: 'comment_id', type: 'integer', nullable: true)]
    private ?int $commentId = null;

    #[ORM\Column(name: 'reason', type: 'string', length: 100, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(name: 'user_reporter_id', type: 'integer', nullable: true)]
    private ?int $userReporterId = null;

    #[ORM\Column(name: 'lu', type: 'boolean', options: ['default' => false])]
    private bool $lu = false;

    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function getSignalementId(): ?int { return $this->signalementId; }
    public function setSignalementId(?int $id): self { $this->signalementId = $id; return $this; }

    public function getCommentId(): ?int { return $this->commentId; }
    public function setCommentId(?int $id): self { $this->commentId = $id; return $this; }

    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): self { $this->reason = $reason; return $this; }

    public function getUserReporterId(): ?int { return $this->userReporterId; }
    public function setUserReporterId(?int $id): self { $this->userReporterId = $id; return $this; }

    public function getLu(): bool { return $this->lu; }
    public function setLu(bool $lu): self { $this->lu = $lu; return $this; }

    public function getDateCreation(): \DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $date): self { $this->dateCreation = $date; return $this; }
}