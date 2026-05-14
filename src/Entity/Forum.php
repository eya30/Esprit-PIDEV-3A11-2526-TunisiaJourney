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
    #[ORM\Column(name: 'idF', type: 'integer')]
    /** @phpstan-ignore-next-line */
    private ?int $idF = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom du forum est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(name: 'theme', type: 'string', length: 150)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un thème.')]
    private ?string $theme = null;

    /** @var Collection<int, Publication> */
    #[ORM\OneToMany(mappedBy: 'forum', targetEntity: Publication::class, cascade: ['persist', 'remove'])]
    private Collection $publications;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
        $this->theme = '';
        $this->nom = '';
    }

    public function getIdF(): ?int { return $this->idF; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }
    public function getTheme(): ?string { return $this->theme; }
    public function setTheme(?string $t): self { $this->theme = $t; return $this; }

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