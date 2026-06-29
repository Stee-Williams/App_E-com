<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
#[ORM\HasLifecycleCallbacks]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['produit:lecture', 'commande:lecture', 'liste_souhaits:lecture', 'avis:lecture'])]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Groups(['produit:lecture', 'produit:ecriture', 'commande:lecture', 'liste_souhaits:lecture'])]
    private ?string $nom = null;

    #[ORM\Column(length: 220, unique: true)]
    #[Groups(['produit:lecture', 'produit:ecriture'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['produit:lecture', 'produit:ecriture'])]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    #[Groups(['produit:lecture', 'produit:ecriture', 'commande:lecture', 'liste_souhaits:lecture'])]
    private ?string $prix = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['produit:lecture', 'produit:ecriture'])]
    private ?string $prixPromo = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['produit:lecture', 'produit:ecriture'])]
    private int $stock = 0;

    #[ORM\Column]
    #[Groups(['produit:lecture', 'produit:ecriture', 'admin:lecture'])]
    private bool $actif = true;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['produit:lecture', 'produit:ecriture', 'commande:lecture', 'liste_souhaits:lecture'])]
    private ?string $imagePrincipale = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['produit:lecture', 'produit:ecriture'])]
    private ?Categorie $categorie = null;

    #[ORM\Column]
    #[Groups(['produit:lecture', 'admin:lecture'])]
    private ?\DateTimeImmutable $dateCreation = null;

    /** @var Collection<int, ImageProduit> */
    #[ORM\OneToMany(targetEntity: ImageProduit::class, mappedBy: 'produit', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['produit:lecture'])]
    private Collection $images;

    /** @var Collection<int, Avis> */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'produit', orphanRemoval: true)]
    #[Groups(['produit:lecture'])]
    private Collection $avis;

    /** @var Collection<int, VarianteProduit> */
    #[ORM\OneToMany(targetEntity: VarianteProduit::class, mappedBy: 'produit', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['produit:lecture'])]
    private Collection $variantes;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->variantes = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateCreation ??= new \DateTimeImmutable();
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

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getPrixPromo(): ?string
    {
        return $this->prixPromo;
    }

    public function setPrixPromo(?string $prixPromo): static
    {
        $this->prixPromo = $prixPromo;

        return $this;
    }

    public function getPrixEffectif(): string
    {
        return $this->prixPromo ?? $this->prix ?? '0.00';
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

    public function getImagePrincipale(): ?string
    {
        return $this->imagePrincipale;
    }

    public function setImagePrincipale(?string $imagePrincipale): static
    {
        $this->imagePrincipale = $imagePrincipale;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /** @return Collection<int, ImageProduit> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ImageProduit $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduit($this);
        }

        return $this;
    }

    /** @return Collection<int, Avis> */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    #[Groups(['produit:lecture'])]
    public function getNoteMoyenne(): ?float
    {
        if ($this->avis->isEmpty()) {
            return null;
        }

        $total = 0;
        foreach ($this->avis as $avis) {
            $total += $avis->getNote();
        }

        return round($total / $this->avis->count(), 1);
    }

    /** @return Collection<int, VarianteProduit> */
    public function getVariantes(): Collection
    {
        return $this->variantes;
    }

    public function addVariante(VarianteProduit $variante): static
    {
        if (!$this->variantes->contains($variante)) {
            $this->variantes->add($variante);
            $variante->setProduit($this);
        }

        return $this;
    }

    #[Groups(['produit:lecture'])]
    public function hasVariantes(): bool
    {
        return !$this->variantes->isEmpty();
    }

    #[Groups(['produit:lecture'])]
    public function getStockDisponible(): int
    {
        if ($this->variantes->isEmpty()) {
            return $this->stock;
        }

        $total = 0;
        foreach ($this->variantes as $variante) {
            if ($variante->isActif()) {
                $total += $variante->getStock();
            }
        }

        return $total;
    }
}
