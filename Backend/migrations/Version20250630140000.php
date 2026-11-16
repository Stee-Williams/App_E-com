<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250630140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend le code postal facultatif pour les adresses simplifiées';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adresse ALTER code_postal DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE adresse SET code_postal = 'N/A' WHERE code_postal IS NULL");
        $this->addSql('ALTER TABLE adresse ALTER code_postal SET NOT NULL');
    }
}
