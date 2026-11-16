<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250630130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne le nom de l’index Stripe sur le nom Doctrine généré';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX IF EXISTS UNIQ_COMMANDE_STRIPE_SESSION RENAME TO UNIQ_6EEAA67D1A314A57');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX IF EXISTS UNIQ_6EEAA67D1A314A57 RENAME TO UNIQ_COMMANDE_STRIPE_SESSION');
    }
}
