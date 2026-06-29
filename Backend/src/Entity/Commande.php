<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
#[ORM\HasLifecycleCallbacks]
class Commande
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRMEE = 'confirmee';
    public const STATUT_EXPEDIEE = 'expediee';
    public const STATUT_LIVREE = 'livree';
    public const STATUT_ANNULEE = 'annulee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $sousTotal = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $fraisLivraison = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $reduction = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $total = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $numero = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $emailInvite = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $prenomInvite = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $nomInvite = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $telephoneInvite = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $adresseInviteLibelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $adresseInviteRue = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $adresseInviteVille = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $adresseInviteCodePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?string $adresseInvitePays = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    #[Groups(['commande:lecture'])]
    private ?string $jetonSuivi = null;

    #[ORM\ManyToOne]
    #[Groups(['commande:lecture'])]
    private ?Adresse $adresseLivraison = null;

    #[ORM\ManyToOne]
    #[Groups(['commande:lecture'])]
    private ?BonReduction $bonReduction = null;

    #[ORM\Column]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private ?\DateTimeImmutable $dateCreation = null;

    /** @var Collection<int, LigneCommande> */
    #[ORM\OneToMany(targetEntity: LigneCommande::class, mappedBy: 'commande', orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['commande:lecture', 'admin:lecture'])]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateCreation ??= new \DateTimeImmutable();
        $this->numero ??= 'CMD-' . strtoupper(bin2hex(random_bytes(4)));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getSousTotal(): ?string
    {
        return $this->sousTotal;
    }

    public function setSousTotal(string $sousTotal): static
    {
        $this->sousTotal = $sousTotal;

        return $this;
    }

    public function getFraisLivraison(): ?string
    {
        return $this->fraisLivraison;
    }

    public function setFraisLivraison(string $fraisLivraison): static
    {
        $this->fraisLivraison = $fraisLivraison;

        return $this;
    }

    public function getReduction(): ?string
    {
        return $this->reduction;
    }

    public function setReduction(string $reduction): static
    {
        $this->reduction = $reduction;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
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

    public function getAdresseLivraison(): ?Adresse
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?Adresse $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }

    public function getBonReduction(): ?BonReduction
    {
        return $this->bonReduction;
    }

    public function setBonReduction(?BonReduction $bonReduction): static
    {
        $this->bonReduction = $bonReduction;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /** @return Collection<int, LigneCommande> */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneCommande $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }

        return $this;
    }

    public function isInvite(): bool
    {
        return $this->utilisateur === null;
    }

    public function getEmailInvite(): ?string
    {
        return $this->emailInvite;
    }

    public function setEmailInvite(?string $emailInvite): static
    {
        $this->emailInvite = $emailInvite;

        return $this;
    }

    public function getPrenomInvite(): ?string
    {
        return $this->prenomInvite;
    }

    public function setPrenomInvite(?string $prenomInvite): static
    {
        $this->prenomInvite = $prenomInvite;

        return $this;
    }

    public function getNomInvite(): ?string
    {
        return $this->nomInvite;
    }

    public function setNomInvite(?string $nomInvite): static
    {
        $this->nomInvite = $nomInvite;

        return $this;
    }

    public function getTelephoneInvite(): ?string
    {
        return $this->telephoneInvite;
    }

    public function setTelephoneInvite(?string $telephoneInvite): static
    {
        $this->telephoneInvite = $telephoneInvite;

        return $this;
    }

    public function getAdresseInviteLibelle(): ?string
    {
        return $this->adresseInviteLibelle;
    }

    public function setAdresseInviteLibelle(?string $adresseInviteLibelle): static
    {
        $this->adresseInviteLibelle = $adresseInviteLibelle;

        return $this;
    }

    public function getAdresseInviteRue(): ?string
    {
        return $this->adresseInviteRue;
    }

    public function setAdresseInviteRue(?string $adresseInviteRue): static
    {
        $this->adresseInviteRue = $adresseInviteRue;

        return $this;
    }

    public function getAdresseInviteVille(): ?string
    {
        return $this->adresseInviteVille;
    }

    public function setAdresseInviteVille(?string $adresseInviteVille): static
    {
        $this->adresseInviteVille = $adresseInviteVille;

        return $this;
    }

    public function getAdresseInviteCodePostal(): ?string
    {
        return $this->adresseInviteCodePostal;
    }

    public function setAdresseInviteCodePostal(?string $adresseInviteCodePostal): static
    {
        $this->adresseInviteCodePostal = $adresseInviteCodePostal;

        return $this;
    }

    public function getAdresseInvitePays(): ?string
    {
        return $this->adresseInvitePays;
    }

    public function setAdresseInvitePays(?string $adresseInvitePays): static
    {
        $this->adresseInvitePays = $adresseInvitePays;

        return $this;
    }

    public function getJetonSuivi(): ?string
    {
        return $this->jetonSuivi;
    }

    public function setJetonSuivi(?string $jetonSuivi): static
    {
        $this->jetonSuivi = $jetonSuivi;

        return $this;
    }

    #[Groups(['commande:lecture', 'admin:lecture'])]
    public function getNomClient(): string
    {
        if ($this->utilisateur) {
            return trim($this->utilisateur->getPrenom() . ' ' . $this->utilisateur->getNom());
        }

        return trim(($this->prenomInvite ?? '') . ' ' . ($this->nomInvite ?? ''));
    }

    #[Groups(['commande:lecture', 'admin:lecture'])]
    public function getAdresseLivraisonComplete(): ?string
    {
        if ($this->adresseLivraison) {
            return $this->adresseLivraison->getAdresseComplete();
        }

        if ($this->adresseInviteRue) {
            return sprintf(
                '%s, %s %s, %s',
                $this->adresseInviteRue,
                $this->adresseInviteCodePostal,
                $this->adresseInviteVille,
                $this->adresseInvitePays
            );
        }

        return null;
    }
}
