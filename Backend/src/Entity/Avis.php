<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\Table(name: 'avis')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'avis_utilisateur_produit', columns: ['utilisateur_id', 'produit_id'])]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['avis:lecture', 'produit:lecture', 'admin:lecture'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['avis:lecture', 'avis:ecriture', 'produit:lecture', 'admin:lecture'])]
    private int $note = 5;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['avis:lecture', 'avis:ecriture', 'produit:lecture', 'admin:lecture'])]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['avis:lecture', 'admin:lecture'])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column]
    #[Groups(['avis:lecture', 'produit:lecture', 'admin:lecture'])]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateCreation ??= new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
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

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
