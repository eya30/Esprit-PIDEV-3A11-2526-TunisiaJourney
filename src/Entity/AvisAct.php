<?php

namespace App\Entity;

use App\Repository\AvisActRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: AvisActRepository::class)]
#[ORM\Table(name: 'avis_act')]
#[Vich\Uploadable]
class AvisAct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idAv', type: 'integer')]
    private ?int $idAv = null;

    #[ORM\ManyToOne(targetEntity: Activite::class)]
    #[ORM\JoinColumn(name: 'IDAct', referencedColumnName: 'IDAct', nullable: false, onDelete: 'CASCADE')]
    private ?Activite $activite = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(name: 'note', type: 'integer')]
    private ?int $note = null;

    #[ORM\Column(name: 'commentaire', type: 'text', nullable: true)]
    private ?string $commentaire = null;

    // Champ image stocké en BDD (nom du fichier)
    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    // Champ virtuel pour VichUploader (pas en BDD)
    #[Vich\UploadableField(mapping: 'avis_images', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'date_avis', type: 'datetime')]
    private ?\DateTimeInterface $dateAvis = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    // ── Getters / Setters ──

    public function getIdAv(): ?int { return $this->idAv; }

    public function getActivite(): ?Activite { return $this->activite; }
    public function setActivite(?Activite $activite): self { $this->activite = $activite; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getNote(): ?int { return $this->note; }
    public function setNote(int $note): self { $this->note = $note; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): self { $this->commentaire = $commentaire; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self { $this->image = $image; return $this; }

    public function getImageFile(): ?File { return $this->imageFile; }
    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        // Obligatoire pour que VichUploader déclenche le update
        if ($imageFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getDateAvis(): ?\DateTimeInterface { return $this->dateAvis; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}