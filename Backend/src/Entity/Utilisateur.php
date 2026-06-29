<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\HasLifecycleCallbacks]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['utilisateur:lecture', 'avis:lecture', 'commande:lecture', 'admin:lecture'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['utilisateur:lecture', 'utilisateur:ecriture', 'admin:lecture'])]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $motDePasse = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['utilisateur:lecture', 'utilisateur:ecriture', 'admin:lecture'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['utilisateur:lecture', 'utilisateur:ecriture', 'admin:lecture'])]
    private ?string $nom = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['utilisateur:lecture', 'utilisateur:ecriture', 'admin:lecture'])]
    private ?string $telephone = null;

    #[ORM\Column]
    #[Groups(['utilisateur:lecture', 'admin:lecture'])]
    private array $roles = [];

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $jetonApi = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $jetonReinitialisation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expirationJetonReinitialisation = null;

    #[ORM\Column]
    #[Groups(['admin:lecture'])]
    private ?\DateTimeImmutable $dateCreation = null;

    /** @var Collection<int, Adresse> */
    #[ORM\OneToMany(targetEntity: Adresse::class, mappedBy: 'utilisateur', orphanRemoval: true)]
    #[Groups(['utilisateur:lecture'])]
    private Collection $adresses;

    /** @var Collection<int, Commande> */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'utilisateur')]
    private Collection $commandes;

    /** @var Collection<int, ElementListeSouhaits> */
    #[ORM\OneToMany(targetEntity: ElementListeSouhaits::class, mappedBy: 'utilisateur', orphanRemoval: true)]
    private Collection $listeSouhaits;

    /** @var Collection<int, Avis> */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'utilisateur')]
    private Collection $avis;

    public function __construct()
    {
        $this->adresses = new ArrayCollection();
        $this->commandes = new ArrayCollection();
        $this->listeSouhaits = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getJetonApi(): ?string
    {
        return $this->jetonApi;
    }

    public function setJetonApi(?string $jetonApi): static
    {
        $this->jetonApi = $jetonApi;

        return $this;
    }

    public function getJetonReinitialisation(): ?string
    {
        return $this->jetonReinitialisation;
    }

    public function setJetonReinitialisation(?string $jetonReinitialisation): static
    {
        $this->jetonReinitialisation = $jetonReinitialisation;

        return $this;
    }

    public function getExpirationJetonReinitialisation(): ?\DateTimeImmutable
    {
        return $this->expirationJetonReinitialisation;
    }

    public function setExpirationJetonReinitialisation(?\DateTimeImmutable $expirationJetonReinitialisation): static
    {
        $this->expirationJetonReinitialisation = $expirationJetonReinitialisation;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /** @return Collection<int, Adresse> */
    public function getAdresses(): Collection
    {
        return $this->adresses;
    }

    public function addAdresse(Adresse $adresse): static
    {
        if (!$this->adresses->contains($adresse)) {
            $this->adresses->add($adresse);
            $adresse->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAdresse(Adresse $adresse): static
    {
        if ($this->adresses->removeElement($adresse) && $adresse->getUtilisateur() === $this) {
            $adresse->setUtilisateur(null);
        }

        return $this;
    }

    /** @return Collection<int, Commande> */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    /** @return Collection<int, ElementListeSouhaits> */
    public function getListeSouhaits(): Collection
    {
        return $this->listeSouhaits;
    }

    /** @return Collection<int, Avis> */
    public function getAvis(): Collection
    {
        return $this->avis;
    }
}
