<?php

namespace App\Entity;

use App\Repository\ElementListeSouhaitsRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;

#[ORM\Entity(repositoryClass: ElementListeSouhaitsRepository::class)]
#[ORM\Table(name: 'element_liste_souhaits')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'liste_souhaits_utilisateur_produit', columns: ['utilisateur_id', 'produit_id'])]
class ElementListeSouhaits
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['liste_souhaits:lecture'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'listeSouhaits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['liste_souhaits:lecture'])]
    private ?Produit $produit = null;

    #[ORM\Column]
    #[Groups(['liste_souhaits:lecture'])]
    private ?\DateTimeImmutable $dateAjout = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateAjout ??= new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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

    public function getDateAjout(): ?\DateTimeImmutable
    {
        return $this->dateAjout;
    }
}
