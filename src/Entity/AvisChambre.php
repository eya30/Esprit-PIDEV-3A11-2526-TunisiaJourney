<?php
// src/Entity/AvisChambre.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AvisChambreRepository;

#[ORM\Entity(repositoryClass: AvisChambreRepository::class)]
#[ORM\Table(name: 'avis_chambre')]
class AvisChambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'avisChambres')]
    #[ORM\JoinColumn(name: 'utilisateur_id', nullable: false)]
    private ?User $utilisateur = null;

    // ========== LES 9 NOTES ==========
   
    #[ORM\Column(name: 'note_confort')]
    private ?int $noteConfort = null;

    #[ORM\Column(name: 'note_services')]
    private ?int $noteServices = null;

    #[ORM\Column(name: 'note_equipements')]
    private ?int $noteEquipements = null;

    #[ORM\Column(name: 'note_proprete')]
    private ?int $noteProprete = null;

    #[ORM\Column(name: 'note_personnel')]
    private ?int $notePersonnel = null;

    #[ORM\Column(name: 'note_emplacement')]
    private ?int $noteEmplacement = null;

    #[ORM\Column(name: 'note_restauration')]
    private ?int $noteRestauration = null;

    #[ORM\Column(name: 'note_prix_qualite')]
    private ?int $notePrixQualite = null;

    #[ORM\Column(name: 'note_calme')]
    private ?int $noteCalme = null;

    // =================================

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'date_creation', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'date_modification', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(name: 'a_ete_modifie', type: 'boolean', options: ['default' => false])]
    private bool $aEteModifie = false;

    #[ORM\Column(name: 'est_publie', type: 'boolean', options: ['default' => true])]
    private bool $estPublie = true;

    // ========== NOUVELLES PROPRIÉTÉS POUR L'ANALYSE DE SENTIMENT ==========
   
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $sentiment = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'non_lue'])]
    private ?string $statutNotification = 'non_lue';

    // ========== GETTERS ET SETTERS ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getNoteConfort(): ?int
    {
        return $this->noteConfort;
    }

    public function setNoteConfort(int $noteConfort): self
    {
        $this->noteConfort = $noteConfort;
        return $this;
    }

    public function getNoteServices(): ?int
    {
        return $this->noteServices;
    }

    public function setNoteServices(int $noteServices): self
    {
        $this->noteServices = $noteServices;
        return $this;
    }

    public function getNoteEquipements(): ?int
    {
        return $this->noteEquipements;
    }

    public function setNoteEquipements(int $noteEquipements): self
    {
        $this->noteEquipements = $noteEquipements;
        return $this;
    }

    public function getNoteProprete(): ?int
    {
        return $this->noteProprete;
    }

    public function setNoteProprete(int $noteProprete): self
    {
        $this->noteProprete = $noteProprete;
        return $this;
    }

    public function getNotePersonnel(): ?int
    {
        return $this->notePersonnel;
    }

    public function setNotePersonnel(int $notePersonnel): self
    {
        $this->notePersonnel = $notePersonnel;
        return $this;
    }

    public function getNoteEmplacement(): ?int
    {
        return $this->noteEmplacement;
    }

    public function setNoteEmplacement(int $noteEmplacement): self
    {
        $this->noteEmplacement = $noteEmplacement;
        return $this;
    }

    public function getNoteRestauration(): ?int
    {
        return $this->noteRestauration;
    }

    public function setNoteRestauration(int $noteRestauration): self
    {
        $this->noteRestauration = $noteRestauration;
        return $this;
    }

    public function getNotePrixQualite(): ?int
    {
        return $this->notePrixQualite;
    }

    public function setNotePrixQualite(int $notePrixQualite): self
    {
        $this->notePrixQualite = $notePrixQualite;
        return $this;
    }

    public function getNoteCalme(): ?int
    {
        return $this->noteCalme;
    }

    public function setNoteCalme(int $noteCalme): self
    {
        $this->noteCalme = $noteCalme;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
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

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function isAEteModifie(): bool
    {
        return $this->aEteModifie;
    }

    public function setAEteModifie(bool $aEteModifie): self
    {
        $this->aEteModifie = $aEteModifie;
        return $this;
    }

    public function isEstPublie(): bool
    {
        return $this->estPublie;
    }

    public function setEstPublie(bool $estPublie): self
    {
        $this->estPublie = $estPublie;
        return $this;
    }

    // ========== GETTERS ET SETTERS POUR L'ANALYSE DE SENTIMENT ==========

    public function getSentiment(): ?string
    {
        return $this->sentiment;
    }

    public function setSentiment(?string $sentiment): self
    {
        $this->sentiment = $sentiment;
        return $this;
    }

    public function getStatutNotification(): ?string
    {
        return $this->statutNotification;
    }

    public function setStatutNotification(?string $statutNotification): self
    {
        $this->statutNotification = $statutNotification;
        return $this;
    }

    /**
     * Calcule la note moyenne générale (sur 5)
     */
    public function getNoteMoyenne(): float
    {
        $total = $this->noteConfort + $this->noteServices + $this->noteEquipements +
                 $this->noteProprete + $this->notePersonnel + $this->noteEmplacement +
                 $this->noteRestauration + $this->notePrixQualite + $this->noteCalme;
       
        return round($total / 9, 1);
    }
}
