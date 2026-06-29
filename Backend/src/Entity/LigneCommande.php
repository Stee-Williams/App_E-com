<?php

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;

#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
#[ORM\Table(name: 'ligne_commande')]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?Produit $produit = null;

    #[ORM\Column]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private int $quantite = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $prixUnitaire = null;

    #[ORM\ManyToOne]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?VarianteProduit $variante = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $libelleVariante = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

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

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getVariante(): ?VarianteProduit
    {
        return $this->variante;
    }

    public function setVariante(?VarianteProduit $variante): static
    {
        $this->variante = $variante;

        return $this;
    }

    public function getLibelleVariante(): ?string
    {
        return $this->libelleVariante;
    }

    public function setLibelleVariante(?string $libelleVariante): static
    {
        $this->libelleVariante = $libelleVariante;

        return $this;
    }

    #[Groups(['commande:lecture', 'admin:lecture'])]
    public function getSousTotal(): string
    {
        return number_format((float) $this->prixUnitaire * $this->quantite, 2, '.', '');
    }
}
