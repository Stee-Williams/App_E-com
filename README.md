# NovaShop - App_E-com

Application e-commerce full-stack:
- `Frontend/`: React + Vite + TypeScript
- `Backend/`: API Symfony + PostgreSQL

## Prérequis

- Node.js 20+
- npm
- PHP 8.2+
- Composer
- Symfony CLI
- PostgreSQL 16+

## Installation rapide

### 1) Backend

```bash
cd Backend
composer install
copy .env.local.example .env.local
```

Configurer ensuite `Backend/.env.local`:
- `POSTGRES_*` pour la base
- `MAILER_DSN` pour SMTP Gmail (mot de passe d'application)
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `APP_FRONTEND_URL`
- `SENEGAL_API_KEY` (facultative) pour augmenter le quota quotidien des suggestions géographiques de l'API 221

Exemple Gmail:

```env
MAILER_DSN=smtp://VOTRE_ADRESSE_GMAIL:VOTRE_MOT_DE_PASSE_APPLICATION@smtp.gmail.com:587?encryption=tls&auth_mode=login
MAIL_FROM_ADDRESS=VOTRE_ADRESSE_GMAIL
MAIL_FROM_NAME=NovaShop
APP_FRONTEND_URL=http://localhost:5173
```

Le parcours de commande enregistre directement la commande et son jeton de suivi, sans paiement en ligne. Aucune clé Stripe ni configuration de webhook n'est nécessaire pour commander.

### Suggestions géographiques

Le backend expose `GET /api/geographie/suggestions?q=thies&limit=5`, qui relaie les suggestions de communes et localités sénégalaises de l'[API 221](https://221.orvlabs.com/documentation/api/geographie). Les réponses sont mises en cache six heures. Sans clé, l'API 221 limite les appels à 100 par jour et par adresse IP; `SENEGAL_API_KEY` est facultative et peut être configurée dans `Backend/.env.local`.

Démarrer l'API:

```bash
symfony serve --listen-ip=127.0.0.1 --port=8000
```

### 2) Frontend

```bash
cd Frontend
npm install
npm run dev
```

## Comptes de démonstration

Fixtures backend:
- Admin: `admin@novashop.fr` / `admin123`
- Client: `client@novashop.fr` / `client123`

## Catalogue sportif de démonstration

Pour ajouter les huit articles de démonstration sans supprimer les données existantes:

```bash
cd Backend
symfony php bin/console doctrine:fixtures:load --append --group=demo_sportswear
```

Les articles, prix et stocks sont fictifs. Les photos illustratives proviennent d'Unsplash et de Pexels; leurs licences sont indiquées dans `Backend/demo-image-credits.txt`.

Des photos JPG peuvent être ajoutées dans `Backend/public/uploads/produits/` pour remplacer les SVG de 15 produits du catalogue initial. Elles ne sont pas incluses dans ce dépôt; les illustrations SVG sont utilisées par défaut.

## Importer le vrai catalogue

Le modèle CSV est dans [Backend/catalogue-template.csv](Backend/catalogue-template.csv). Il utilise le séparateur `;`, des montants en FCFA avec un point décimal, et les catégories déjà créées (colonne `categorie_slug`). Les images doivent déjà être présentes dans `Backend/public/uploads/produits/`. La colonne facultative `variantes_json` accepte une liste JSON, par exemple `[{"taille":"M","couleur":"Noir","stock":4}]` (dans une cellule CSV entre guillemets, doubler les guillemets internes).

Valider d'abord le fichier sans modifier la base, puis appliquer explicitement:

```bash
cd Backend
symfony console app:catalogue:import C:\chemin\catalogue.csv
symfony console app:catalogue:import C:\chemin\catalogue.csv --apply
```

L'import ajoute ou met à jour les produits par slug et ne supprime pas les produits absents du fichier. Un fichier Excel `.xlsx` doit être exporté en CSV UTF-8 séparé par des points-virgules. Les données réelles ne sont pas incluses tant que le fichier source n'a pas été fourni.

## Préparer le VPS Docker

Les images de production et le fichier [compose.production.yaml](compose.production.yaml) sont prêts pour un VPS avec Docker Compose. Copier `.env.production.example` vers `.env.production`, remplacer chaque valeur d'exemple par des secrets générés, utiliser le même mot de passe pour PostgreSQL et `DATABASE_URL` (encodé pour l'URL), configurer le DNS du domaine vers le VPS, puis:

```bash
docker compose --env-file .env.production -f compose.production.yaml build
docker compose --env-file .env.production -f compose.production.yaml up -d database
docker compose --env-file .env.production -f compose.production.yaml run --rm backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose --env-file .env.production -f compose.production.yaml up -d
```

Seul Caddy publie les ports web et gère le HTTPS automatique; la base et l'API restent privées dans le réseau Docker. PostgreSQL et les images envoyées sont stockés dans des volumes persistants. Docker n'étant pas disponible sur le poste de développement, la construction et le déploiement devront être vérifiés sur le VPS.

## Scripts utiles

### Frontend
- `npm run dev`
- `npm run build`
- `npm run lint`

### Backend
- `php bin/console about`
- `php bin/console doctrine:migrations:migrate`

## Emails transactionnels

Le backend envoie des emails pour:
- inscription (bienvenue)
- mot de passe oublié
- mise à jour de statut de commande

En développement, `MAILER_DSN=null://null` désactive l'envoi réel. Pour Gmail, utiliser un mot de passe d'application et encoder les caractères réservés dans l'URL SMTP. Les identifiants doivent être configurés localement dans `Backend/.env.local` ou dans les secrets du VPS, jamais dans Git.
