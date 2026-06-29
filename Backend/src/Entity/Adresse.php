<?php

namespace App\Entity;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdresseRepository::class)]
#[ORM\Table(name: 'adresse')]
class Adresse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['adresse:lecture', 'utilisateur:lecture', 'commande:lecture'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['adresse:lecture', 'adresse:ecriture', 'utilisateur:lecture'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['adresse:lecture', 'adresse:ecriture', 'utilisateur:lecture', 'commande:lecture'])]
    private ?string $rue = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['adresse:lecture', 'adresse:ecriture', 'utilisateur:lecture', 'commande:lecture'])]
    private ?string $ville = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['adresse:lecture', 'adresse:ecriture', 'utilisateur:lecture', 'commande:lecture'])]
    private ?string $codePostal = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['adresse:lecture', 'adresse:ecriture', 'utilisateur:lecture', 'commande:lecture'])]
    private ?string $pays = null;

    #[ORM\Column]
    #[Groups(['adresse:lecture', 'adresse:ecriture', 'utilisateur:lecture'])]
    private bool $parDefaut = false;

    #[ORM\ManyToOne(inversedBy: 'adresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getRue(): ?string
    {
        return $this->rue;
    }

    public function setRue(string $rue): static
    {
        $this->rue = $rue;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function isParDefaut(): bool
    {
        return $this->parDefaut;
    }

    public function setParDefaut(bool $parDefaut): static
    {
        $this->parDefaut = $parDefaut;

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

    #[Groups(['commande:lecture'])]
    public function getAdresseComplete(): string
    {
        return sprintf('%s, %s %s, %s', $this->rue, $this->codePostal, $this->ville, $this->pays);
    }
}
