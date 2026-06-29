<?php

namespace App\Service;

use App\Entity\BonReduction;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Entity\VarianteProduit;
use App\Repository\BonReductionRepository;
use App\Repository\ProduitRepository;
use App\Repository\VarianteProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommandeService
{
    private const FRAIS_LIVRAISON = 2500;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProduitRepository $produitRepository,
        private readonly VarianteProduitRepository $varianteRepository,
        private readonly BonReductionRepository $bonReductionRepository,
    ) {
    }

    /**
     * @param array<int, array{produitId: int, quantite: int, varianteId?: int}> $articles
     */
    public function creerCommande(Utilisateur $utilisateur, array $articles, ?int $adresseId = null, ?string $codeBon = null): Commande
    {
        return $this->em->wrapInTransaction(function () use ($utilisateur, $articles, $adresseId, $codeBon): Commande {
            $commande = new Commande();
            $commande->setUtilisateur($utilisateur);
            $commande->setFraisLivraison((string) self::FRAIS_LIVRAISON);

            if ($adresseId) {
                foreach ($utilisateur->getAdresses() as $adresse) {
                    if ($adresse->getId() === $adresseId) {
                        $commande->setAdresseLivraison($adresse);
                        break;
                    }
                }
            }

            $this->remplirLignes($commande, $articles);
            $this->appliquerBonEtTotal($commande, $codeBon);

            $this->em->persist($commande);
            $this->em->flush();

            return $commande;
        });
    }

    /**
     * @param array{
     *   email: string,
     *   prenom: string,
     *   nom: string,
     *   telephone?: string,
     *   adresse: array{libelle?: string, rue: string, ville: string, codePostal: string, pays: string}
     * } $invite
     * @param array<int, array{produitId: int, quantite: int, varianteId?: int}> $articles
     */
    public function creerCommandeInvite(array $invite, array $articles, ?string $codeBon = null): Commande
    {
        $email = trim($invite['email'] ?? '');
        $prenom = trim($invite['prenom'] ?? '');
        $nom = trim($invite['nom'] ?? '');
        $adresse = $invite['adresse'] ?? [];

        if ($email === '' || $prenom === '' || $nom === '') {
            throw new \InvalidArgumentException('Email, prénom et nom sont obligatoires.');
        }

        if (empty($adresse['rue']) || empty($adresse['ville']) || empty($adresse['codePostal']) || empty($adresse['pays'])) {
            throw new \InvalidArgumentException('Adresse de livraison incomplète.');
        }

        return $this->em->wrapInTransaction(function () use ($email, $prenom, $nom, $invite, $adresse, $articles, $codeBon): Commande {
            $commande = new Commande();
            $commande->setFraisLivraison((string) self::FRAIS_LIVRAISON);
            $commande->setEmailInvite($email);
            $commande->setPrenomInvite($prenom);
            $commande->setNomInvite($nom);
            $commande->setTelephoneInvite($invite['telephone'] ?? null);
            $commande->setAdresseInviteLibelle($adresse['libelle'] ?? 'Livraison');
            $commande->setAdresseInviteRue((string) $adresse['rue']);
            $commande->setAdresseInviteVille((string) $adresse['ville']);
            $commande->setAdresseInviteCodePostal((string) $adresse['codePostal']);
            $commande->setAdresseInvitePays((string) $adresse['pays']);
            $commande->setJetonSuivi(bin2hex(random_bytes(24)));

            $this->remplirLignes($commande, $articles);
            $this->appliquerBonEtTotal($commande, $codeBon);

            $this->em->persist($commande);
            $this->em->flush();

            return $commande;
        });
    }

    /**
     * @param array<int, array{produitId: int, quantite: int, varianteId?: int}> $articles
     */
    private function remplirLignes(Commande $commande, array $articles): void
    {
        $articles = $this->normaliserArticles($articles);
        $sousTotal = 0.0;

        foreach ($articles as $article) {
            $produit = $this->produitRepository->findVerrouillePourMiseAJour($article['produitId']);
            if (!$produit instanceof Produit || !$produit->isActif()) {
                throw new \InvalidArgumentException('Produit introuvable.');
            }

            $quantite = $article['quantite'];
            $variante = null;

            if (!empty($article['varianteId'])) {
                $variante = $this->varianteRepository->findVerrouillePourMiseAJour((int) $article['varianteId']);
                if (!$variante || $variante->getProduit()?->getId() !== $produit->getId() || !$variante->isActif()) {
                    throw new \InvalidArgumentException(sprintf('Variante invalide pour %s.', $produit->getNom()));
                }
                if ($variante->getStock() < $quantite) {
                    throw new \InvalidArgumentException(sprintf('Stock insuffisant pour %s (%s).', $produit->getNom(), $variante->getLibelle()));
                }
                $variante->setStock($variante->getStock() - $quantite);
            } elseif ($produit->hasVariantes()) {
                throw new \InvalidArgumentException(sprintf('Sélectionnez une variante pour %s.', $produit->getNom()));
            } elseif ($produit->getStock() < $quantite) {
                throw new \InvalidArgumentException(sprintf('Stock insuffisant pour %s.', $produit->getNom()));
            } else {
                $produit->setStock($produit->getStock() - $quantite);
            }

            $prix = (float) $produit->getPrixEffectif();
            $sousTotal += $prix * $quantite;

            $ligne = new LigneCommande();
            $ligne->setProduit($produit);
            $ligne->setQuantite($quantite);
            $ligne->setPrixUnitaire(number_format($prix, 2, '.', ''));
            if ($variante) {
                $ligne->setVariante($variante);
                $ligne->setLibelleVariante($variante->getLibelle());
            }
            $commande->addLigne($ligne);
        }

        $commande->setSousTotal(number_format($sousTotal, 2, '.', ''));
    }

    private function appliquerBonEtTotal(Commande $commande, ?string $codeBon): void
    {
        $sousTotal = (float) $commande->getSousTotal();
        $reduction = 0.0;

        if ($codeBon) {
            $bon = $this->bonReductionRepository->findByCode($codeBon);
            if ($bon instanceof BonReduction) {
                $reduction = $bon->calculerReduction($sousTotal);
                if ($reduction > 0) {
                    $commande->setBonReduction($bon);
                    $bon->incrementerUtilisations();
                }
            }
        }

        $commande->setReduction(number_format($reduction, 2, '.', ''));
        $total = $sousTotal + self::FRAIS_LIVRAISON - $reduction;
        $commande->setTotal(number_format(max(0, $total), 2, '.', ''));
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     *
     * @return array<int, array{produitId: int, quantite: int, varianteId?: int}>
     */
    private function normaliserArticles(array $articles): array
    {
        $groupes = [];

        foreach ($articles as $article) {
            if (!is_array($article)) {
                continue;
            }

            $id = (int) ($article['produitId'] ?? $article['productId'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $varianteId = (int) ($article['varianteId'] ?? 0);
            $cle = $varianteId > 0 ? $id . '-' . $varianteId : (string) $id;
            $quantite = max(1, (int) ($article['quantite'] ?? $article['quantity'] ?? 1));
            $groupes[$cle] = [
                'produitId' => $id,
                'quantite' => ($groupes[$cle]['quantite'] ?? 0) + $quantite,
                'varianteId' => $varianteId > 0 ? $varianteId : null,
            ];
        }

        if ($groupes === []) {
            throw new \InvalidArgumentException('Le panier est vide.');
        }

        return array_values($groupes);
    }
}
