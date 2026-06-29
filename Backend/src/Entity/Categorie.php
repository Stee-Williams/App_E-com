<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(name: 'categorie')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['categorie:lecture', 'produit:lecture'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Groups(['categorie:lecture', 'categorie:ecriture', 'produit:lecture'])]
    private ?string $nom = null;

    #[ORM\Column(length: 150, unique: true)]
    #[Groups(['categorie:lecture', 'categorie:ecriture'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['categorie:lecture', 'categorie:ecriture'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['categorie:lecture', 'categorie:ecriture'])]
    private ?string $image = null;

    /** @var Collection<int, Produit> */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'categorie')]
    private Collection $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /** @return Collection<int, Produit> */
    public function getProduits(): Collection
    {
        return $this->produits;
    }
}
