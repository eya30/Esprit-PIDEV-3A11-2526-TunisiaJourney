<?php
namespace App\Entity;

use App\Repository\ForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ForumRepository::class)]
#[ORM\Table(name: 'forum')]
class Forum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_f', type: 'integer')]
    /** @phpstan-ignore property.onlyRead */
    private int $idF;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom du forum est obligatoire.')]
    #[Assert\Length(min: 3, max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\p{Arabic}0-9]/u',
        message: 'Le nom du forum doit commencer par une lettre ou un chiffre.'
    )]
    private ?string $nom = null;

    #[ORM\Column(name: 'theme', type: 'string', length: 150)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un thème.')]
    private ?string $theme = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => 'actif'])]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un statut.')]
    #[Assert\Choice(choices: ['actif', 'inactif'], message: 'Le statut doit être "actif" ou "inactif".')]
    private string $status = 'actif';

    /** @var Collection<int, Publication> */
    #[ORM\OneToMany(
        mappedBy: 'forum',
        targetEntity: Publication::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $publications;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
        $this->status = 'actif';
    }

    public function getIdF(): int { return $this->idF ?? 0; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }

    public function getTheme(): ?string { return $this->theme; }
    public function setTheme(?string $t): self { $this->theme = $t; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): self { $this->status = $s; return $this; }

    /** @return Collection<int, Publication> */
    public function getPublications(): Collection { return $this->publications; }

    public function addPublication(Publication $p): self
    {
        if (!$this->publications->contains($p)) {
            $this->publications[] = $p;
            $p->setForum($this);
        }
        return $this;
    }

    public function removePublication(Publication $p): self
    {
        if ($this->publications->removeElement($p) && $p->getForum() === $this) {
            $p->setForum(null);
        }
        return $this;
    }
}