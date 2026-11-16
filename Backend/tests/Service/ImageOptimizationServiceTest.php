<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizationService;
use PHPUnit\Framework\TestCase;

final class ImageOptimizationServiceTest extends TestCase
{
    private string $projectDir;
    private string $imagesDir;

    protected function setUp(): void
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            self::markTestSkipped('GD avec WebP est requis pour ces tests.');
        }

        $this->projectDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'novashop-images-' . bin2hex(random_bytes(6));
        $this->imagesDir = $this->projectDir . '/public/uploads/produits';
        mkdir($this->imagesDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->projectDir) && is_dir($this->projectDir)) {
            $this->supprimerDossier($this->projectDir);
        }
    }

    public function testCreeUnSrcSetAdapteAuxDimensionsSource(): void
    {
        $source = $this->creerImage('produit.png', 1000, 500);
        $optimiseur = new ImageOptimizationService($this->projectDir);

        $optimiseur->genererVariantes($source);

        self::assertSame(
            '/uploads/produits/produit-480.webp 480w, /uploads/produits/produit-960.webp 960w',
            $optimiseur->obtenirSrcSet('/uploads/produits/produit.png'),
        );
        self::assertSame(480, getimagesize($this->imagesDir . '/produit-480.webp')[0]);
        self::assertSame(960, getimagesize($this->imagesDir . '/produit-960.webp')[0]);
    }

    public function testNeGenerePasDeVariantePlusLargeQueLaSource(): void
    {
        $source = $this->creerImage('petit.png', 213, 300);
        $optimiseur = new ImageOptimizationService($this->projectDir);

        $optimiseur->genererVariantes($source);

        self::assertSame(
            '/uploads/produits/petit-213.webp 213w',
            $optimiseur->obtenirSrcSet('/uploads/produits/petit.png'),
        );
        self::assertFileDoesNotExist($this->imagesDir . '/petit-480.webp');
    }

    private function creerImage(string $nom, int $largeur, int $hauteur): string
    {
        $image = imagecreatetruecolor($largeur, $hauteur);
        imagefill($image, 0, 0, imagecolorallocate($image, 40, 100, 160));
        $chemin = $this->imagesDir . DIRECTORY_SEPARATOR . $nom;
        imagepng($image, $chemin);
        imagedestroy($image);

        return $chemin;
    }

    private function supprimerDossier(string $dossier): void
    {
        foreach (new \FilesystemIterator($dossier) as $entree) {
            if ($entree->isDir()) {
                $this->supprimerDossier($entree->getPathname());
            } else {
                unlink($entree->getPathname());
            }
        }

        rmdir($dossier);
    }
}
