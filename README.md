# NovaShop - App_E-com

Application e-commerce full-stack:
- `Frontend/`: React + Vite + TypeScript
- `Backend/`: API Symfony + PostgreSQL

## Prérequis

- Node.js 20+
- npm
- PHP 8.2+
- Composer
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

Exemple Gmail:

```env
MAILER_DSN=smtp://VOTRE_ADRESSE_GMAIL:VOTRE_MOT_DE_PASSE_APPLICATION@smtp.gmail.com:587?encryption=tls&auth_mode=login
MAIL_FROM_ADDRESS=VOTRE_ADRESSE_GMAIL
MAIL_FROM_NAME=NovaShop
APP_FRONTEND_URL=http://localhost:5173
```

Démarrer l'API:

```bash
php -S localhost:8000 -t public
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

## Scripts utiles

### Frontend
- `npm run dev`
- `npm run build`
- `npm run lint`

### Backend
- `php bin/console about`
- `php bin/console doctrine:migrations:migrate`

## Emails transactionnels

Le backend envoie maintenant des emails pour:
- inscription (bienvenue)
- mot de passe oublié
- confirmation de commande
- mise à jour de statut de commande

En développement, `MAILER_DSN=null://null` désactive l'envoi réel.
