<?php

namespace App\Entity;

use App\Repository\VarianteProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VarianteProduitRepository::class)]
#[ORM\Table(name: 'variante_produit')]
class VarianteProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['variante:lecture', 'produit:lecture', 'commande:lecture'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'variantes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['variante:lecture', 'variante:ecriture', 'produit:lecture', 'commande:lecture'])]
    private ?string $taille = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['variante:lecture', 'variante:ecriture', 'produit:lecture', 'commande:lecture'])]
    private ?string $couleur = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['variante:lecture', 'variante:ecriture', 'produit:lecture'])]
    private int $stock = 0;

    #[ORM\Column]
    #[Groups(['variante:lecture', 'variante:ecriture', 'produit:lecture'])]
    private bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    #[Groups(['variante:lecture', 'produit:lecture', 'commande:lecture'])]
    public function getLibelle(): string
    {
        $parties = array_filter([$this->taille, $this->couleur]);

        return $parties !== [] ? implode(' / ', $parties) : 'Standard';
    }
}
