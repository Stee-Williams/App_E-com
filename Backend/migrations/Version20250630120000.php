<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250630120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du suivi des paiements Stripe aux commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE commande ADD statut_paiement VARCHAR(30) DEFAULT 'sans_paiement' NOT NULL");
        $this->addSql('ALTER TABLE commande ADD stripe_session_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMMANDE_STRIPE_SESSION ON commande (stripe_session_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_COMMANDE_STRIPE_SESSION');
        $this->addSql('ALTER TABLE commande DROP statut_paiement');
        $this->addSql('ALTER TABLE commande DROP stripe_session_id');
    }
}
