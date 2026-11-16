<?php

namespace App\Service;

final class ImageOptimizationService
{
    private const TAILLES_CIBLES = [480, 960];
    private const TYPES_IMAGE = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function genererVariantes(string $cheminSource): void
    {
        if (!is_file($cheminSource) || !is_readable($cheminSource)) {
            throw new \RuntimeException('Le fichier image est introuvable ou illisible.');
        }

        if (!in_array(strtolower(pathinfo($cheminSource, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return;
        }

        $informations = getimagesize($cheminSource);
        if ($informations === false || !in_array($informations['mime'], self::TYPES_IMAGE, true)) {
            return;
        }

        if (!function_exists('imagewebp') || !function_exists('imagecreatefromwebp')) {
            throw new \RuntimeException('La prise en charge WebP de GD est requise pour optimiser les images.');
        }

        $source = match ($informations['mime']) {
            'image/jpeg' => imagecreatefromjpeg($cheminSource),
            'image/png' => imagecreatefrompng($cheminSource),
            'image/webp' => imagecreatefromwebp($cheminSource),
        };

        if ($source === false) {
            throw new \RuntimeException('Impossible de lire l’image à optimiser.');
        }

        $largeurSource = $informations[0];
        $hauteurSource = $informations[1];

        foreach ($this->largeursVariantes($largeurSource) as $largeur) {
            $hauteur = max(1, (int) round($hauteurSource * $largeur / $largeurSource));
            $variante = imagecreatetruecolor($largeur, $hauteur);
            if ($informations['mime'] === 'image/jpeg') {
                imagefill($variante, 0, 0, imagecolorallocate($variante, 255, 255, 255));
            } else {
                imagealphablending($variante, false);
                imagesavealpha($variante, true);
                imagefill($variante, 0, 0, imagecolorallocatealpha($variante, 0, 0, 0, 127));
            }

            if (!imagecopyresampled($variante, $source, 0, 0, 0, 0, $largeur, $hauteur, $largeurSource, $hauteurSource)) {
                imagedestroy($variante);
                imagedestroy($source);
                throw new \RuntimeException('Impossible de redimensionner l’image.');
            }

            $cheminVariante = $this->cheminVariante($cheminSource, $largeur);
            $resultat = imagewebp($variante, $cheminVariante, 82);
            imagedestroy($variante);

            if (!$resultat) {
                imagedestroy($source);
                throw new \RuntimeException('Impossible d’enregistrer la variante WebP.');
            }
        }

        imagedestroy($source);
    }

    public function obtenirSrcSet(?string $cheminRelatif): ?string
    {
        if ($cheminRelatif === null || !str_starts_with($cheminRelatif, '/uploads/produits/')) {
            return null;
        }

        $nom = substr($cheminRelatif, strlen('/uploads/produits/'));
        if ($nom === '' || basename($nom) !== $nom) {
            return null;
        }

        $cheminSource = $this->projectDir . '/public/uploads/produits/' . $nom;
        if (!is_file($cheminSource) || !is_readable($cheminSource)) {
            return null;
        }

        if (!in_array(strtolower(pathinfo($nom, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $informations = getimagesize($cheminSource);
        if ($informations === false || !in_array($informations['mime'], self::TYPES_IMAGE, true)) {
            return null;
        }

        $nomSansExtension = pathinfo($nom, PATHINFO_FILENAME);
        $variantes = [];
        foreach ($this->largeursVariantes($informations[0]) as $largeur) {
            $nomVariante = $nomSansExtension . '-' . $largeur . '.webp';
            $cheminVariante = $this->projectDir . '/public/uploads/produits/' . $nomVariante;
            if (is_file($cheminVariante)) {
                $variantes[] = '/uploads/produits/' . $nomVariante . ' ' . $largeur . 'w';
            }
        }

        return $variantes === [] ? null : implode(', ', $variantes);
    }

    /**
     * @return int[]
     */
    private function largeursVariantes(int $largeurSource): array
    {
        $largeurs = array_values(array_filter(
            self::TAILLES_CIBLES,
            static fn (int $largeur): bool => $largeur < $largeurSource,
        ));
        if ($largeurSource <= max(self::TAILLES_CIBLES)) {
            $largeurs[] = $largeurSource;
        }

        return array_values(array_unique($largeurs));
    }

    private function cheminVariante(string $cheminSource, int $largeur): string
    {
        $nom = pathinfo($cheminSource, PATHINFO_FILENAME);

        return dirname($cheminSource) . DIRECTORY_SEPARATOR . $nom . '-' . $largeur . '.webp';
    }
}
