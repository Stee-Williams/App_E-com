<?php

namespace App\Entity;

use App\Repository\ImageProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;

#[ORM\Entity(repositoryClass: ImageProduitRepository::class)]
#[ORM\Table(name: 'image_produit')]
class ImageProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['produit:lecture'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['produit:lecture'])]
    private ?string $chemin = null;

    #[ORM\Column]
    #[Groups(['produit:lecture'])]
    private int $ordre = 0;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChemin(): ?string
    {
        return $this->chemin;
    }

    public function setChemin(string $chemin): static
    {
        $this->chemin = $chemin;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
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
}
