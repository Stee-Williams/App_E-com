<?php

namespace App\Tests\Command;

use App\Command\ImportCatalogueCommand;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportCatalogueCommandTest extends TestCase
{
    public function testLeFichierInvalideEchoueSansInterrogerLesCategories(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'novashop-catalogue-');
        self::assertNotFalse($fichier);
        file_put_contents($fichier, implode("\n", [
            'slug;nom;description;prix;prix_promo;stock;categorie_slug;image;actif;variantes_json',
            'Slug-Majuscule;Chaussures;Description;10000;;3;sport;;;[]',
        ]));

        try {
            $categories = $this->createMock(CategorieRepository::class);
            $categories->expects(self::never())->method('findOneBy');
            $command = new ImportCatalogueCommand(
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(ProduitRepository::class),
                $categories,
                sys_get_temp_dir(),
            );
            $tester = new CommandTester($command);

            self::assertSame(Command::FAILURE, $tester->execute(['fichier' => $fichier]));
            self::assertStringContainsString('slug invalide', $tester->getDisplay());
        } finally {
            unlink($fichier);
        }
    }

    public function testLeFichierDoitAvoirToutesLesColonnes(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'novashop-catalogue-');
        self::assertNotFalse($fichier);
        file_put_contents($fichier, "slug;nom;prix\n");

        try {
            $command = new ImportCatalogueCommand(
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(ProduitRepository::class),
                $this->createMock(CategorieRepository::class),
                sys_get_temp_dir(),
            );
            $tester = new CommandTester($command);

            self::assertSame(Command::FAILURE, $tester->execute(['fichier' => $fichier]));
            self::assertStringContainsString('Colonnes requises', $tester->getDisplay());
        } finally {
            unlink($fichier);
        }
    }
}
