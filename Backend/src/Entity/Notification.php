<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    public const TYPE_COMMANDE = 'commande';
    public const TYPE_PROMO = 'promo';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification:lecture'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 30)]
    #[Groups(['notification:lecture'])]
    private string $type = self::TYPE_COMMANDE;

    #[ORM\Column(length: 150)]
    #[Groups(['notification:lecture'])]
    private string $titre = '';

    #[ORM\Column(type: 'text')]
    #[Groups(['notification:lecture'])]
    private string $message = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['notification:lecture'])]
    private ?string $lien = null;

    #[ORM\Column]
    #[Groups(['notification:lecture'])]
    private bool $lu = false;

    #[ORM\Column]
    #[Groups(['notification:lecture'])]
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

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function setLien(?string $lien): static
    {
        $this->lien = $lien;

        return $this;
    }

    public function isLu(): bool
    {
        return $this->lu;
    }

    public function setLu(bool $lu): static
    {
        $this->lu = $lu;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
