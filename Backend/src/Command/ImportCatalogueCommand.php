<?php

namespace App\Command;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\VarianteProduit;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:catalogue:import',
    description: 'Valide ou importe un catalogue CSV sans supprimer les produits absents du fichier',
)]
class ImportCatalogueCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProduitRepository $produitRepository,
        private readonly CategorieRepository $categorieRepository,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('fichier', InputArgument::REQUIRED, 'Chemin du fichier CSV (séparateur ;)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Appliquer les changements après validation complète');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fichier = (string) $input->getArgument('fichier');

        try {
            $lignes = $this->lireEtValider($fichier);
        } catch (\RuntimeException|\UnexpectedValueException|\JsonException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('%d ligne(s) valide(s).', count($lignes)));
        if (!$input->getOption('apply')) {
            $io->note('Aucune donnée modifiée. Relancez avec --apply après avoir vérifié le fichier.');

            return Command::SUCCESS;
        }

        $this->em->wrapInTransaction(function () use ($lignes): void {
            foreach ($lignes as $ligne) {
                $produit = $this->produitRepository->findOneBy(['slug' => $ligne['slug']]) ?? new Produit();
                $produit
                    ->setSlug($ligne['slug'])
                    ->setNom($ligne['nom'])
                    ->setDescription($ligne['description'])
                    ->setPrix($ligne['prix'])
                    ->setPrixPromo($ligne['prixPromo'])
                    ->setStock($ligne['stock'])
                    ->setActif($ligne['actif'])
                    ->setCategorie($ligne['categorie']);

                if ($ligne['image'] !== null) {
                    $produit->setImagePrincipale($ligne['image']);
                }

                $this->em->persist($produit);
                foreach ($ligne['variantes'] as $donneesVariante) {
                    $variante = $this->trouverVariante($produit, $donneesVariante['taille'], $donneesVariante['couleur'])
                        ?? new VarianteProduit();
                    $variante
                        ->setProduit($produit)
                        ->setTaille($donneesVariante['taille'])
                        ->setCouleur($donneesVariante['couleur'])
                        ->setStock($donneesVariante['stock'])
                        ->setActif($donneesVariante['actif']);
                    $this->em->persist($variante);
                }
            }

            $this->em->flush();
        });

        $io->success(sprintf('%d produit(s) importé(s) ou mis à jour.', count($lignes)));

        return Command::SUCCESS;
    }

    /**
     * @return list<array{
     *   slug: string,
     *   nom: string,
     *   description: ?string,
     *   prix: string,
     *   prixPromo: ?string,
     *   stock: int,
     *   categorie: Categorie,
     *   image: ?string,
     *   actif: bool,
     *   variantes: list<array{taille: ?string, couleur: ?string, stock: int, actif: bool}>
     * }>
     */
    private function lireEtValider(string $fichier): array
    {
        if (!is_file($fichier) || !is_readable($fichier)) {
            throw new \RuntimeException('Fichier CSV introuvable ou illisible : ' . $fichier);
        }

        $handle = fopen($fichier, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Impossible d’ouvrir le fichier CSV.');
        }

        try {
            $entetes = fgetcsv($handle, null, ';', '"', '');
            if (!is_array($entetes)) {
                throw new \UnexpectedValueException('Le fichier CSV est vide.');
            }
            $entetes[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $entetes[0]) ?? (string) $entetes[0];
            $entetes = array_map(static fn ($entete) => trim((string) $entete), $entetes);
            $requises = ['slug', 'nom', 'description', 'prix', 'prix_promo', 'stock', 'categorie_slug', 'image', 'actif', 'variantes_json'];
            if (array_diff($requises, $entetes) !== []) {
                throw new \UnexpectedValueException('Colonnes requises : ' . implode('; ', $requises));
            }
            if (count($entetes) !== count(array_unique($entetes))) {
                throw new \UnexpectedValueException('Le fichier contient des en-têtes en double.');
            }

            $index = array_flip($entetes);
            $slugs = [];
            $lignes = [];
            $numeroLigne = 1;
            while (($valeurs = fgetcsv($handle, null, ';', '"', '')) !== false) {
                ++$numeroLigne;
                if ($valeurs === [null] || $valeurs === []) {
                    continue;
                }
                if (count($valeurs) !== count($entetes)) {
                    throw new \UnexpectedValueException(sprintf('Ligne %d : nombre de colonnes incorrect.', $numeroLigne));
                }

                $cellules = [];
                foreach ($index as $nom => $position) {
                    $cellules[$nom] = trim((string) ($valeurs[$position] ?? ''));
                }
                $ligne = $this->validerLigne($cellules, $numeroLigne);
                if (isset($slugs[$ligne['slug']])) {
                    throw new \UnexpectedValueException(sprintf('Ligne %d : slug répété (%s).', $numeroLigne, $ligne['slug']));
                }
                $slugs[$ligne['slug']] = true;
                $lignes[] = $ligne;
            }

            if ($lignes === []) {
                throw new \UnexpectedValueException('Le fichier ne contient aucun produit.');
            }

            return $lignes;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, string> $cellules
     *
     * @return array{
     *   slug: string,
     *   nom: string,
     *   description: ?string,
     *   prix: string,
     *   prixPromo: ?string,
     *   stock: int,
     *   categorie: Categorie,
     *   image: ?string,
     *   actif: bool,
     *   variantes: list<array{taille: ?string, couleur: ?string, stock: int, actif: bool}>
     * }
     */
    private function validerLigne(array $cellules, int $numeroLigne): array
    {
        $erreur = static fn (string $message) => new \UnexpectedValueException(sprintf('Ligne %d : %s', $numeroLigne, $message));
        $slug = $cellules['slug'];
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw $erreur('slug invalide (minuscules, chiffres et tirets uniquement).');
        }
        if ($cellules['nom'] === '' || mb_strlen($cellules['nom']) > 200) {
            throw $erreur('nom obligatoire (200 caractères maximum).');
        }
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $cellules['prix'])) {
            throw $erreur('prix invalide (montant FCFA, décimales avec un point).');
        }
        $prix = number_format((float) $cellules['prix'], 2, '.', '');
        $prixPromo = null;
        if ($cellules['prix_promo'] !== '') {
            if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $cellules['prix_promo'])) {
                throw $erreur('prix_promo invalide.');
            }
            $prixPromo = number_format((float) $cellules['prix_promo'], 2, '.', '');
            if ((float) $prixPromo <= 0 || (float) $prixPromo >= (float) $prix) {
                throw $erreur('prix_promo doit être positif et inférieur au prix.');
            }
        }
        if (!preg_match('/^\d+$/', $cellules['stock'])) {
            throw $erreur('stock doit être un entier positif ou nul.');
        }
        if (!in_array(strtolower($cellules['actif']), ['1', '0', 'true', 'false'], true)) {
            throw $erreur('actif doit valoir 1, 0, true ou false.');
        }

        $categorie = $this->categorieRepository->findOneBy(['slug' => $cellules['categorie_slug']]);
        if (!$categorie instanceof Categorie) {
            throw $erreur('catégorie inconnue : ' . $cellules['categorie_slug']);
        }

        return [
            'slug' => $slug,
            'nom' => $cellules['nom'],
            'description' => $cellules['description'] !== '' ? $cellules['description'] : null,
            'prix' => $prix,
            'prixPromo' => $prixPromo,
            'stock' => (int) $cellules['stock'],
            'categorie' => $categorie,
            'image' => $this->validerImage($cellules['image'], $numeroLigne),
            'actif' => in_array(strtolower($cellules['actif']), ['1', 'true'], true),
            'variantes' => $this->lireVariantes($cellules['variantes_json'], $numeroLigne),
        ];
    }

    private function validerImage(string $image, int $numeroLigne): ?string
    {
        if ($image === '') {
            return null;
        }

        $prefixe = '/uploads/produits/';
        $nomFichier = str_starts_with($image, $prefixe) ? substr($image, strlen($prefixe)) : $image;
        if (basename($nomFichier) !== $nomFichier
            || !preg_match('/^[\pL\pN._-]+$/u', $nomFichier)
            || str_contains($nomFichier, '..')
            || !in_array(strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)
        ) {
            throw new \UnexpectedValueException(sprintf('Ligne %d : image invalide (nom de fichier local attendu).', $numeroLigne));
        }

        $chemin = $this->projectDir . '/public/uploads/produits/' . $nomFichier;
        if (!is_file($chemin)) {
            throw new \UnexpectedValueException(sprintf('Ligne %d : image introuvable dans public/uploads/produits : %s', $numeroLigne, $nomFichier));
        }

        return $prefixe . $nomFichier;
    }

    /**
     * @return list<array{taille: ?string, couleur: ?string, stock: int, actif: bool}>
     */
    private function lireVariantes(string $json, int $numeroLigne): array
    {
        if ($json === '') {
            return [];
        }

        $variantes = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($variantes) || !array_is_list($variantes)) {
            throw new \UnexpectedValueException(sprintf('Ligne %d : variantes_json doit être une liste JSON.', $numeroLigne));
        }

        $resultat = [];
        $cles = [];
        foreach ($variantes as $variante) {
            if (!is_array($variante)) {
                throw new \UnexpectedValueException(sprintf('Ligne %d : variante JSON invalide.', $numeroLigne));
            }
            $taille = isset($variante['taille']) ? trim((string) $variante['taille']) : null;
            $couleur = isset($variante['couleur']) ? trim((string) $variante['couleur']) : null;
            if (($taille === null || $taille === '') && ($couleur === null || $couleur === '')) {
                throw new \UnexpectedValueException(sprintf('Ligne %d : chaque variante doit préciser une taille ou une couleur.', $numeroLigne));
            }
            if (!isset($variante['stock']) || filter_var($variante['stock'], FILTER_VALIDATE_INT) === false || (int) $variante['stock'] < 0) {
                throw new \UnexpectedValueException(sprintf('Ligne %d : stock de variante invalide.', $numeroLigne));
            }
            $taille = $taille !== '' ? $taille : null;
            $couleur = $couleur !== '' ? $couleur : null;
            $cle = ($taille ?? '') . "\0" . ($couleur ?? '');
            if (isset($cles[$cle])) {
                throw new \UnexpectedValueException(sprintf('Ligne %d : variante répétée.', $numeroLigne));
            }
            $cles[$cle] = true;
            $actif = $variante['actif'] ?? true;
            if (!is_bool($actif)) {
                throw new \UnexpectedValueException(sprintf('Ligne %d : actif de variante doit être un booléen JSON.', $numeroLigne));
            }
            $resultat[] = [
                'taille' => $taille,
                'couleur' => $couleur,
                'stock' => (int) $variante['stock'],
                'actif' => $actif,
            ];
        }

        return $resultat;
    }

    private function trouverVariante(Produit $produit, ?string $taille, ?string $couleur): ?VarianteProduit
    {
        foreach ($produit->getVariantes() as $variante) {
            if ($variante->getTaille() === $taille && $variante->getCouleur() === $couleur) {
                return $variante;
            }
        }

        return null;
    }
}
