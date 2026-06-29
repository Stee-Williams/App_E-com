<?php

namespace App\DataFixtures;

use App\Entity\BonReduction;
use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Entity\VarianteProduit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Service\MotDePasseService;

class AppFixtures extends Fixture
{
    private string $dossierImages;

    public function __construct(
        private readonly MotDePasseService $motDePasseService,
    ) {
        $this->dossierImages = dirname(__DIR__, 2) . '/public/uploads/produits';
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new Utilisateur();
        $admin->setEmail('admin@novashop.fr');
        $admin->setPrenom('Admin');
        $admin->setNom('NovaShop');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setMotDePasse($this->motDePasseService->hash($admin, 'admin123'));
        $admin->setJetonApi(bin2hex(random_bytes(32)));
        $manager->persist($admin);

        $client = new Utilisateur();
        $client->setEmail('client@novashop.fr');
        $client->setPrenom('Marie');
        $client->setNom('Dupont');
        $client->setMotDePasse($this->motDePasseService->hash($client, 'client123'));
        $client->setJetonApi(bin2hex(random_bytes(32)));
        $manager->persist($client);

        $categories = [
            ['nom' => 'Électronique', 'slug' => 'electronique', 'description' => 'Gadgets et appareils high-tech'],
            ['nom' => 'Mode', 'slug' => 'mode', 'description' => 'Vêtements et accessoires tendance'],
            ['nom' => 'Maison', 'slug' => 'maison', 'description' => 'Décoration et équipement pour la maison'],
            ['nom' => 'Sport', 'slug' => 'sport', 'description' => 'Équipements et vêtements de sport'],
        ];

        $categorieEntities = [];
        foreach ($categories as $data) {
            $categorie = new Categorie();
            $categorie->setNom($data['nom']);
            $categorie->setSlug($data['slug']);
            $categorie->setDescription($data['description']);
            $manager->persist($categorie);
            $categorieEntities[] = $categorie;
        }

        $produits = [
            // Électronique
            ['slug' => 'casque-bluetooth-pro', 'nom' => 'Casque Bluetooth Pro', 'prix' => '45900', 'prixPromo' => '35900', 'stock' => 45, 'categorie' => 0, 'description' => 'Casque sans fil avec réduction de bruit active et autonomie 30h.', 'icone' => 'casque', 'couleur' => 265],
            ['slug' => 'montre-connectee-fit', 'nom' => 'Montre Connectée Fit', 'prix' => '97500', 'stock' => 30, 'categorie' => 0, 'description' => 'Suivi santé, GPS intégré et résistance à l\'eau IP68.', 'icone' => 'montre', 'couleur' => 200],
            ['slug' => 'enceinte-portable', 'nom' => 'Enceinte Portable', 'prix' => '39300', 'stock' => 70, 'categorie' => 0, 'description' => 'Son stéréo puissant, Bluetooth 5.0, étanche IPX7.', 'icone' => 'enceinte', 'couleur' => 15],
            ['slug' => 'smartphone-nova-x-pro', 'nom' => 'Smartphone Nova X Pro', 'prix' => '125000', 'prixPromo' => '99900', 'stock' => 35, 'categorie' => 0, 'description' => 'Écran AMOLED 6,7", triple capteur 108 Mpx, charge rapide 65W.', 'icone' => 'telephone', 'couleur' => 220],
            ['slug' => 'tablette-lite-10', 'nom' => 'Tablette Lite 10"', 'prix' => '89000', 'stock' => 22, 'categorie' => 0, 'description' => 'Tablette 10 pouces, 128 Go, idéale pour le travail et le streaming.', 'icone' => 'tablette', 'couleur' => 185],
            ['slug' => 'ecouteurs-true-wireless', 'nom' => 'Écouteurs True Wireless', 'prix' => '28500', 'prixPromo' => '22900', 'stock' => 90, 'categorie' => 0, 'description' => 'Écouteurs intra-auriculaires, réduction de bruit, boîtier 24h.', 'icone' => 'ecouteurs', 'couleur' => 300],
            ['slug' => 'clavier-mecanique-rgb', 'nom' => 'Clavier Mécanique RGB', 'prix' => '42000', 'stock' => 40, 'categorie' => 0, 'description' => 'Switches mécaniques, rétroéclairage RGB, format compact 75%.', 'icone' => 'clavier', 'couleur' => 280],
            ['slug' => 'webcam-4k-pro-stream', 'nom' => 'Webcam 4K Pro Stream', 'prix' => '35800', 'stock' => 28, 'categorie' => 0, 'description' => 'Webcam 4K 60 fps, micro intégré, idéale télétravail et streaming.', 'icone' => 'webcam', 'couleur' => 160],
            // Mode
            ['slug' => 't-shirt-coton-bio', 'nom' => 'T-shirt Coton Bio', 'prix' => '19500', 'stock' => 120, 'categorie' => 1, 'description' => 'T-shirt unisexe en coton biologique, coupe regular.', 'icone' => 'tshirt', 'couleur' => 140],
            ['slug' => 'veste-denim-classic', 'nom' => 'Veste Denim Classic', 'prix' => '52000', 'prixPromo' => '39000', 'stock' => 25, 'categorie' => 1, 'description' => 'Veste en denim stretch, style intemporel.', 'icone' => 'veste', 'couleur' => 210],
            ['slug' => 'sac-a-dos-urbain', 'nom' => 'Sac à Dos Urbain', 'prix' => '32700', 'stock' => 40, 'categorie' => 1, 'description' => 'Sac à dos 20L avec compartiment laptop 15".', 'icone' => 'sac', 'couleur' => 25],
            ['slug' => 'robe-soiree-elegante', 'nom' => 'Robe Soirée Élégante', 'prix' => '45000', 'stock' => 18, 'categorie' => 1, 'description' => 'Robe longue en satin, coupe fluide, disponible en plusieurs coloris.', 'icone' => 'robe', 'couleur' => 330],
            ['slug' => 'sneakers-running-pro', 'nom' => 'Sneakers Running Pro', 'prix' => '38000', 'prixPromo' => '29900', 'stock' => 55, 'categorie' => 1, 'description' => 'Chaussures de running légères, semelle amortissante réactive.', 'icone' => 'sneakers', 'couleur' => 350],
            ['slug' => 'montre-classique-cuir', 'nom' => 'Montre Classique Cuir', 'prix' => '41500', 'stock' => 32, 'categorie' => 1, 'description' => 'Montre analogique, bracelet cuir véritable, mouvement quartz.', 'icone' => 'montre', 'couleur' => 30],
            // Maison
            ['slug' => 'lampe-design-led', 'nom' => 'Lampe Design LED', 'prix' => '29800', 'stock' => 60, 'categorie' => 2, 'description' => 'Lampe de bureau à intensité variable, design minimaliste.', 'icone' => 'lampe', 'couleur' => 45],
            ['slug' => 'set-coussins-deco', 'nom' => 'Set Coussins Déco', 'prix' => '22900', 'stock' => 80, 'categorie' => 2, 'description' => 'Lot de 4 coussins décoratifs aux motifs géométriques.', 'icone' => 'coussin', 'couleur' => 10],
            ['slug' => 'cafetiere-expresso-italiana', 'nom' => 'Cafetière Expresso Italiana', 'prix' => '67500', 'stock' => 20, 'categorie' => 2, 'description' => 'Machine expresso 15 bars, broyeur intégré, mousseur lait.', 'icone' => 'cafe', 'couleur' => 20],
            ['slug' => 'aspirateur-robot-smart', 'nom' => 'Aspirateur Robot Smart', 'prix' => '112000', 'prixPromo' => '89900', 'stock' => 14, 'categorie' => 2, 'description' => 'Aspiration et lavage, cartographie laser, application mobile.', 'icone' => 'aspirateur', 'couleur' => 195],
            ['slug' => 'purificateur-air-hepa', 'nom' => 'Purificateur d\'Air HEPA', 'prix' => '54900', 'stock' => 26, 'categorie' => 2, 'description' => 'Filtre HEPA H13, capteur qualité de l\'air, mode silencieux nuit.', 'icone' => 'air', 'couleur' => 170],
            // Sport
            ['slug' => 'tapis-yoga-premium', 'nom' => 'Tapis de Yoga Premium', 'prix' => '25500', 'stock' => 55, 'categorie' => 3, 'description' => 'Tapis antidérapant 6mm, matériau écologique.', 'icone' => 'yoga', 'couleur' => 120],
            ['slug' => 'halteres-ajustables', 'nom' => 'Haltères Ajustables', 'prix' => '84500', 'prixPromo' => '64900', 'stock' => 15, 'categorie' => 3, 'description' => 'Paire d\'haltères réglables de 2 à 24 kg.', 'icone' => 'halteres', 'couleur' => 240],
            ['slug' => 'velo-appartement-fold', 'nom' => 'Vélo d\'Appartement Fold', 'prix' => '195000', 'stock' => 8, 'categorie' => 3, 'description' => 'Vélo elliptique pliable, 12 niveaux de résistance, écran LCD.', 'icone' => 'velo', 'couleur' => 0],
            ['slug' => 'raquette-tennis-pro-carbon', 'nom' => 'Raquette Tennis Pro Carbon', 'prix' => '48500', 'stock' => 24, 'categorie' => 3, 'description' => 'Raquette carbone légère, équilibre manche, cordage pré-monté.', 'icone' => 'raquette', 'couleur' => 90],
            ['slug' => 'sac-sport-impermeable', 'nom' => 'Sac de Sport Imperméable', 'prix' => '21500', 'stock' => 48, 'categorie' => 3, 'description' => 'Sac 40L, compartiment chaussures, bandoulière renforcée.', 'icone' => 'sac', 'couleur' => 260],
            ['slug' => 'gourde-isotherme-750ml', 'nom' => 'Gourde Isotherme 750ml', 'prix' => '12500', 'stock' => 100, 'categorie' => 3, 'description' => 'Acier inoxydable, conserve 24h froid / 12h chaud, sans BPA.', 'icone' => 'gourde', 'couleur' => 200],
        ];

        if (!is_dir($this->dossierImages)) {
            mkdir($this->dossierImages, 0775, true);
        }

        foreach ($produits as $data) {
            $slug = $data['slug'];
            $cheminImage = $this->assurerImage($slug, $data['nom'], $data['icone'], $data['couleur'], true);

            $produit = new Produit();
            $produit->setNom($data['nom']);
            $produit->setSlug($slug);
            $produit->setDescription($data['description']);
            $produit->setPrix($data['prix']);
            $produit->setPrixPromo($data['prixPromo'] ?? null);
            $produit->setStock($data['stock']);
            $produit->setCategorie($categorieEntities[$data['categorie']]);
            $produit->setImagePrincipale($cheminImage);
            $manager->persist($produit);

            if ($data['categorie'] === 1) {
                foreach ($this->variantesMode() as $varianteData) {
                    $variante = new VarianteProduit();
                    $variante->setTaille($varianteData['taille']);
                    $variante->setCouleur($varianteData['couleur']);
                    $variante->setStock($varianteData['stock']);
                    $produit->addVariante($variante);
                }
                $produit->setStock(0);
            }
        }

        $bon = new BonReduction();
        $bon->setCode('BIENVENUE10');
        $bon->setType(BonReduction::TYPE_POURCENTAGE);
        $bon->setValeur('10.00');
        $bon->setMontantMinimum('25000.00');
        $bon->setUtilisationsMax(100);
        $manager->persist($bon);

        $bonFixe = new BonReduction();
        $bonFixe->setCode('LIVRAISON5');
        $bonFixe->setType(BonReduction::TYPE_MONTANT_FIXE);
        $bonFixe->setValeur('2500.00');
        $bonFixe->setMontantMinimum('40000.00');
        $manager->persist($bonFixe);

        $manager->flush();
    }

    /** @return array<int, array{taille: string, couleur: string, stock: int}> */
    private function variantesMode(): array
    {
        return [
            ['taille' => 'S', 'couleur' => 'Noir', 'stock' => 8],
            ['taille' => 'M', 'couleur' => 'Noir', 'stock' => 12],
            ['taille' => 'L', 'couleur' => 'Noir', 'stock' => 10],
            ['taille' => 'M', 'couleur' => 'Blanc', 'stock' => 6],
            ['taille' => 'L', 'couleur' => 'Bleu', 'stock' => 5],
        ];
    }

    private function assurerImage(string $slug, string $nom, string $icone, int $couleur, bool $forcer = false): string
    {
        $fichier = $this->dossierImages . '/' . $slug . '.svg';
        if ($forcer || !file_exists($fichier)) {
            file_put_contents($fichier, $this->genererSvg($nom, $icone, $couleur));
        }

        return '/uploads/produits/' . $slug . '.svg';
    }

    private function genererSvg(string $nom, string $icone, int $hue): string
    {
        $hue2 = ($hue + 35) % 360;
        $forme = $this->formeIcone($icone);
        $titre = htmlspecialchars(mb_strlen($nom) > 28 ? mb_substr($nom, 0, 26) . '…' : $nom, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:hsl({$hue},48%,38%)"/>
      <stop offset="100%" style="stop-color:hsl({$hue2},55%,24%)"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="35%" r="55%">
      <stop offset="0%" style="stop-color:rgba(255,255,255,0.18)"/>
      <stop offset="100%" style="stop-color:rgba(255,255,255,0)"/>
    </radialGradient>
  </defs>
  <rect width="800" height="800" fill="url(#bg)"/>
  <rect width="800" height="800" fill="url(#glow)"/>
  <g transform="translate(400,310)" fill="none" stroke="rgba(255,255,255,0.88)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round">
    {$forme}
  </g>
  <text x="400" y="580" text-anchor="middle" fill="rgba(255,255,255,0.92)" font-family="system-ui,sans-serif" font-size="28" font-weight="600">{$titre}</text>
  <text x="400" y="620" text-anchor="middle" fill="rgba(255,255,255,0.45)" font-family="system-ui,sans-serif" font-size="16" letter-spacing="4">NOVASHOP</text>
</svg>
SVG;
    }

    private function formeIcone(string $icone): string
    {
        return match ($icone) {
            'casque' => '<path d="M-90 10a90 90 0 0 1 180 0v50a90 90 0 0 1-180 0z M-90 60v30a90 90 0 0 0 180 0V60"/><rect x="-110" y="50" width="40" height="70" rx="12" fill="rgba(255,255,255,0.15)" stroke="none"/><rect x="70" y="50" width="40" height="70" rx="12" fill="rgba(255,255,255,0.15)" stroke="none"/>',
            'montre' => '<circle cx="0" cy="0" r="95"/><circle cx="0" cy="0" r="75" fill="rgba(255,255,255,0.08)" stroke="none"/><line x1="0" y1="0" x2="0" y2="-50"/><line x1="0" y1="0" x2="35" y2="15"/><rect x="-12" y="-115" width="24" height="35" rx="6" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="-12" y="80" width="24" height="35" rx="6" fill="rgba(255,255,255,0.2)" stroke="none"/>',
            'enceinte' => '<rect x="-70" y="-60" width="140" height="120" rx="20"/><circle cx="0" cy="10" r="35"/><circle cx="0" cy="10" r="18" fill="rgba(255,255,255,0.15)" stroke="none"/>',
            'telephone' => '<rect x="-55" y="-110" width="110" height="220" rx="18"/><rect x="-45" y="-95" width="90" height="175" rx="8" fill="rgba(255,255,255,0.1)" stroke="none"/><circle cx="0" cy="95" r="8" fill="rgba(255,255,255,0.3)" stroke="none"/>',
            'tablette' => '<rect x="-90" y="-65" width="180" height="130" rx="14"/><rect x="-78" y="-53" width="156" height="106" rx="6" fill="rgba(255,255,255,0.1)" stroke="none"/><circle cx="0" cy="78" r="6" fill="rgba(255,255,255,0.3)" stroke="none"/>',
            'ecouteurs' => '<circle cx="-55" cy="0" r="40"/><circle cx="55" cy="0" r="40"/><path d="M-55-40c0-50 25-80 55-80s55 30 55 80"/><rect x="-75" y="30" width="40" height="55" rx="10" fill="rgba(255,255,255,0.15)" stroke="none"/><rect x="35" y="30" width="40" height="55" rx="10" fill="rgba(255,255,255,0.15)" stroke="none"/>',
            'clavier' => '<rect x="-110" y="-40" width="220" height="80" rx="12"/><rect x="-95" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="-70" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="-45" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="-20" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="5" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="30" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="55" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/><rect x="80" y="-25" width="18" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="none"/>',
            'webcam' => '<rect x="-70" y="-30" width="140" height="60" rx="30"/><circle cx="0" cy="0" r="22"/><circle cx="0" cy="0" r="10" fill="rgba(255,255,255,0.2)" stroke="none"/><path d="M70 0h30l20 35v30H70z"/>',
            'tshirt' => '<path d="M-90-40h50l40-50 40 50h50l-20 50v90H-70v-90z"/><line x1="-30" y1="10" x2="30" y2="10"/>',
            'veste' => '<path d="M-100-50h45l15 30 15-30h45l10 120H-110z"/><line x1="-60" y1="-20" x2="-60" y2="70"/><line x1="60" y1="-20" x2="60" y2="70"/>',
            'sac' => '<rect x="-70" y="-30" width="140" height="110" rx="12"/><path d="M-40-30V-60a40 40 0 0 1 80 0v30"/><rect x="-50" y="10" width="100" height="50" rx="6" fill="rgba(255,255,255,0.12)" stroke="none"/>',
            'robe' => '<path d="M-20-80 0-50 20-80 60 100H-60z"/><ellipse cx="0" cy="-70" rx="25" ry="12"/>',
            'sneakers' => '<path d="M-90 20h180l-25 45H-75z"/><path d="M-70 20c20-40 50-55 80-40"/><ellipse cx="20" cy="55" rx="12" ry="8" fill="rgba(255,255,255,0.15)" stroke="none"/>',
            'lampe' => '<rect x="-15" y="40" width="30" height="70"/><path d="M-60 40h120l-30-90H-30z"/><ellipse cx="0" cy="-55" rx="35" ry="12" fill="rgba(255,255,255,0.2)" stroke="none"/>',
            'coussin' => '<rect x="-80" y="-50" width="70" height="70" rx="14" transform="rotate(-8)"/><rect x="10" y="-50" width="70" height="70" rx="14" transform="rotate(8)"/>',
            'cafe' => '<rect x="-50" y="-20" width="100" height="90" rx="10"/><rect x="-35" y="-60" width="70" height="45" rx="8"/><path d="M50 0h25a25 25 0 0 1 0 50H50"/>',
            'aspirateur' => '<circle cx="0" cy="40" r="55"/><circle cx="0" cy="40" r="35" fill="rgba(255,255,255,0.1)" stroke="none"/><rect x="-25" y="-80" width="50" height="90" rx="10"/><circle cx="0" cy="-90" r="8" fill="rgba(255,255,255,0.3)" stroke="none"/>',
            'air' => '<rect x="-55" y="-70" width="110" height="140" rx="16"/><line x1="-30" y1="-30" x2="30" y2="-30"/><line x1="-30" y1="0" x2="30" y2="0"/><line x1="-30" y1="30" x2="30" y2="30"/><path d="M70-20c20 10 20 40 0 50M70 30c20 10 20 40 0 50"/>',
            'yoga' => '<rect x="-110" y="-20" width="220" height="40" rx="8"/><ellipse cx="-60" cy="0" rx="25" ry="12" fill="rgba(255,255,255,0.15)" stroke="none"/><ellipse cx="0" cy="0" rx="25" ry="12" fill="rgba(255,255,255,0.15)" stroke="none"/><ellipse cx="60" cy="0" rx="25" ry="12" fill="rgba(255,255,255,0.15)" stroke="none"/>',
            'halteres' => '<rect x="-110" y="-15" width="50" height="30" rx="6"/><rect x="60" y="-15" width="50" height="30" rx="6"/><rect x="-60" y="-8" width="120" height="16" rx="4"/>',
            'velo' => '<circle cx="-55" cy="30" r="45"/><circle cx="55" cy="30" r="45"/><path d="M-55 30 0-40 55 30"/><line x1="0" y1="-40" x2="0" y2="-75"/><line x1="0" y1="-75" x2="25" y2="-90"/>',
            'raquette' => '<ellipse cx="0" cy="-20" rx="55" ry="70"/><line x1="0" y1="50" x2="0" y2="110"/><line x1="-35" y1="-20" x2="35" y2="-20"/><line x1="0" y1="-75" x2="0" y2="35"/>',
            'gourde' => '<rect x="-30" y="-40" width="60" height="120" rx="20"/><rect x="-18" y="-70" width="36" height="35" rx="8"/><rect x="-10" y="-85" width="20" height="18" rx="4"/>',
            default => '<rect x="-70" y="-70" width="140" height="140" rx="20"/><circle cx="0" cy="0" r="35"/>',
        };
    }
}
