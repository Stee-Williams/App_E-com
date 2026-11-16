<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\VarianteProduit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class DemoSportswearFixtures extends Fixture implements FixtureGroupInterface
{
    private const PRODUCTS = [
        [
            'slug' => 'baskets-pace-01',
            'nom' => 'Baskets Pace 01',
            'description' => 'Une silhouette claire et épurée pour les sorties actives et le quotidien. Article fictif de démonstration ; photo illustrative non contractuelle.',
            'prix' => '44900.00',
            'categorie' => 'mode',
            'image' => '/uploads/produits/demo-baskets-pace.jpg',
            'variantes' => [
                ['taille' => '39', 'couleur' => 'Blanc', 'stock' => 5],
                ['taille' => '40', 'couleur' => 'Blanc', 'stock' => 8],
                ['taille' => '41', 'couleur' => 'Blanc', 'stock' => 8],
                ['taille' => '42', 'couleur' => 'Blanc', 'stock' => 7],
                ['taille' => '43', 'couleur' => 'Blanc', 'stock' => 5],
                ['taille' => '44', 'couleur' => 'Blanc', 'stock' => 3],
            ],
        ],
        [
            'slug' => 't-shirt-core',
            'nom' => 'T-shirt Core',
            'description' => 'Un essentiel au style minimal, facile à associer à une tenue sportive ou décontractée. Article fictif de démonstration ; photo illustrative non contractuelle.',
            'prix' => '15900.00',
            'categorie' => 'mode',
            'image' => '/uploads/produits/demo-tshirt-core.jpg',
            'variantes' => [
                ['taille' => 'S', 'couleur' => 'Blanc', 'stock' => 8],
                ['taille' => 'M', 'couleur' => 'Blanc', 'stock' => 12],
                ['taille' => 'L', 'couleur' => 'Blanc', 'stock' => 10],
                ['taille' => 'XL', 'couleur' => 'Blanc', 'stock' => 5],
            ],
        ],
        [
            'slug' => 'legging-motion',
            'nom' => 'Legging Motion',
            'description' => 'Une pièce sobre pensée pour composer une tenue de sport confortable et dynamique. Article fictif de démonstration ; photo illustrative non contractuelle.',
            'prix' => '24900.00',
            'categorie' => 'mode',
            'image' => '/uploads/produits/demo-legging-motion.jpg',
            'variantes' => [
                ['taille' => 'S', 'couleur' => 'Noir', 'stock' => 7],
                ['taille' => 'M', 'couleur' => 'Noir', 'stock' => 10],
                ['taille' => 'L', 'couleur' => 'Noir', 'stock' => 8],
                ['taille' => 'XL', 'couleur' => 'Noir', 'stock' => 4],
            ],
        ],
        [
            'slug' => 'brassiere-studio',
            'nom' => 'Brassière Studio',
            'description' => 'Une brassière au coloris doux, à porter seule ou sous une veste légère. Article fictif de démonstration ; photo illustrative non contractuelle.',
            'prix' => '21900.00',
            'categorie' => 'mode',
            'image' => '/uploads/produits/demo-brassiere-studio.jpg',
            'variantes' => [
                ['taille' => 'S', 'couleur' => 'Rose', 'stock' => 6],
                ['taille' => 'M', 'couleur' => 'Rose', 'stock' => 9],
                ['taille' => 'L', 'couleur' => 'Rose', 'stock' => 7],
                ['taille' => 'XL', 'couleur' => 'Rose', 'stock' => 3],
            ],
        ],
        [
            'slug' => 'haut-training-flow',
            'nom' => 'Haut Training Flow',
            'description' => 'Une pièce de sport au design contemporain pour compléter une tenue d’entraînement. Article fictif de démonstration ; photo illustrative non contractuelle.',
            'prix' => '22900.00',
            'categorie' => 'mode',
            'image' => '/uploads/produits/demo-haut-training.jpg',
            'variantes' => [
                ['taille' => 'S', 'couleur' => 'Multicolore', 'stock' => 5],
                ['taille' => 'M', 'couleur' => 'Multicolore', 'stock' => 8],
                ['taille' => 'L', 'couleur' => 'Multicolore', 'stock' => 6],
            ],
        ],
        [
            'slug' => 'tapis-training-grip',
            'nom' => 'Tapis Training Grip',
            'description' => 'Un accessoire de démonstration pour les séances au sol et les étirements. Article fictif ; photo illustrative non contractuelle.',
            'prix' => '17500.00',
            'stock' => 24,
            'categorie' => 'sport',
            'image' => '/uploads/produits/demo-tapis-training.jpg',
        ],
        [
            'slug' => 'casquette-pace',
            'nom' => 'Casquette Pace',
            'description' => 'Une casquette au look simple pour compléter une tenue de sport ou de ville. Article fictif de démonstration ; photo illustrative non contractuelle.',
            'prix' => '12500.00',
            'stock' => 30,
            'categorie' => 'mode',
            'image' => '/uploads/produits/demo-casquette-pace.jpg',
        ],
        [
            'slug' => 'sac-sport-urban',
            'nom' => 'Sac Sport Urban',
            'description' => 'Un sac de sport au style polyvalent, présenté ici dans une scène d’entraînement. Article fictif de démonstration ; photo illustrative non contractuelle.',
            'prix' => '24900.00',
            'stock' => 18,
            'categorie' => 'sport',
            'image' => '/uploads/produits/demo-sac-urban.jpg',
        ],
    ];

    public static function getGroups(): array
    {
        return ['demo_sportswear'];
    }

    public function load(ObjectManager $manager): void
    {
        $categoryRepository = $manager->getRepository(Categorie::class);
        $categories = [];

        foreach ([
            'mode' => ['Mode', 'Vêtements et accessoires de sport'],
            'sport' => ['Sport', 'Équipements et accessoires de sport'],
        ] as $slug => [$name, $description]) {
            $category = $categoryRepository->findOneBy(['slug' => $slug]);
            if (!$category instanceof Categorie) {
                $category = (new Categorie())
                    ->setNom($name)
                    ->setSlug($slug)
                    ->setDescription($description);
                $manager->persist($category);
            }

            $categories[$slug] = $category;
        }

        $productRepository = $manager->getRepository(Produit::class);
        foreach (self::PRODUCTS as $data) {
            if ($productRepository->findOneBy(['slug' => $data['slug']]) instanceof Produit) {
                continue;
            }

            $product = (new Produit())
                ->setNom($data['nom'])
                ->setSlug($data['slug'])
                ->setDescription($data['description'])
                ->setPrix($data['prix'])
                ->setStock($data['stock'] ?? 0)
                ->setActif(true)
                ->setCategorie($categories[$data['categorie']])
                ->setImagePrincipale($data['image']);

            foreach ($data['variantes'] ?? [] as $variantData) {
                $variant = (new VarianteProduit())
                    ->setTaille($variantData['taille'])
                    ->setCouleur($variantData['couleur'])
                    ->setStock($variantData['stock']);
                $product->addVariante($variant);
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
