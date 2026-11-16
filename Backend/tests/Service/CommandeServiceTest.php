<?php

namespace App\Tests\Service;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Repository\BonReductionRepository;
use App\Repository\ProduitRepository;
use App\Repository\VarianteProduitRepository;
use App\Service\CommandeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CommandeServiceTest extends TestCase
{
    public function testUneCommandeInviteEstEnregistreeSansPaiementEnAttente(): void
    {
        $produit = (new Produit())
            ->setNom('Produit de test')
            ->setPrix('1200.00')
            ->setStock(5)
            ->setActif(true);

        $produits = $this->createMock(ProduitRepository::class);
        $produits->expects(self::once())
            ->method('findVerrouillePourMiseAJour')
            ->with(1)
            ->willReturn($produit);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $service = new CommandeService(
            $entityManager,
            $produits,
            $this->createMock(VarianteProduitRepository::class),
            $this->createMock(BonReductionRepository::class),
        );

        $commande = $service->creerCommandeInvite(
            [
                'email' => 'client@example.com',
                'prenom' => 'Awa',
                'nom' => 'Diop',
                'adresse' => [
                    'rue' => 'Rue 1',
                    'ville' => 'Dakar',
                    'codePostal' => '10000',
                    'pays' => 'Sénégal',
                ],
            ],
            [['produitId' => 1, 'quantite' => 2]],
        );

        self::assertSame(Commande::PAIEMENT_SANS_PAIEMENT, $commande->getStatutPaiement());
        self::assertSame(Commande::STATUT_EN_ATTENTE, $commande->getStatut());
        self::assertSame('4900.00', $commande->getTotal());
        self::assertSame(3, $produit->getStock());
        self::assertNotEmpty($commande->getJetonSuivi());
    }
}
