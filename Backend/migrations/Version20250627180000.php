<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250627180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression des anciennes tables en anglais non utilisées par NovaShop';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS order_item CASCADE');
        $this->addSql('DROP TABLE IF EXISTS review CASCADE');
        $this->addSql('DROP TABLE IF EXISTS address CASCADE');
        $this->addSql('DROP TABLE IF EXISTS product_image CASCADE');
        $this->addSql('DROP TABLE IF EXISTS wishlist_item CASCADE');
        $this->addSql('DROP TABLE IF EXISTS orders CASCADE');
        $this->addSql('DROP TABLE IF EXISTS product CASCADE');
        $this->addSql('DROP TABLE IF EXISTS coupon CASCADE');
        $this->addSql('DROP TABLE IF EXISTS users CASCADE');
        $this->addSql('DROP TABLE IF EXISTS category CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('-- Tables anglaises obsolètes : pas de restauration automatique');
    }
}
