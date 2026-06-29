<?php

namespace App\Entity;

use App\Repository\BonReductionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonReductionRepository::class)]
#[ORM\Table(name: 'bon_reduction')]
class BonReduction
{
    public const TYPE_POURCENTAGE = 'pourcentage';
    public const TYPE_MONTANT_FIXE = 'montant_fixe';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['bon_reduction:lecture', 'commande:lecture', 'admin:lecture'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private ?string $code = null;

    #[ORM\Column(length: 20)]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private string $type = self::TYPE_POURCENTAGE;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private ?string $valeur = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private ?string $montantMinimum = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private ?int $utilisationsMax = null;

    #[ORM\Column]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private int $utilisations = 0;

    #[ORM\Column]
    #[Groups(['bon_reduction:lecture', 'bon_reduction:ecriture', 'admin:lecture'])]
    private bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getMontantMinimum(): ?string
    {
        return $this->montantMinimum;
    }

    public function setMontantMinimum(?string $montantMinimum): static
    {
        $this->montantMinimum = $montantMinimum;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getUtilisationsMax(): ?int
    {
        return $this->utilisationsMax;
    }

    public function setUtilisationsMax(?int $utilisationsMax): static
    {
        $this->utilisationsMax = $utilisationsMax;

        return $this;
    }

    public function getUtilisations(): int
    {
        return $this->utilisations;
    }

    public function setUtilisations(int $utilisations): static
    {
        $this->utilisations = $utilisations;

        return $this;
    }

    public function incrementerUtilisations(): void
    {
        ++$this->utilisations;
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

    public function estValide(float $montantPanier = 0): bool
    {
        if (!$this->actif) {
            return false;
        }

        $now = new \DateTimeImmutable();
        if ($this->dateDebut && $now < $this->dateDebut) {
            return false;
        }
        if ($this->dateFin && $now > $this->dateFin) {
            return false;
        }
        if ($this->utilisationsMax !== null && $this->utilisations >= $this->utilisationsMax) {
            return false;
        }
        if ($this->montantMinimum !== null && $montantPanier < (float) $this->montantMinimum) {
            return false;
        }

        return true;
    }

    public function calculerReduction(float $montantPanier): float
    {
        if (!$this->estValide($montantPanier)) {
            return 0.0;
        }

        if ($this->type === self::TYPE_POURCENTAGE) {
            return round($montantPanier * ((float) $this->valeur / 100), 2);
        }

        return min((float) $this->valeur, $montantPanier);
    }
}
