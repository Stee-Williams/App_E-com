<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des index pour les filtres et tris fréquents du catalogue et des commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_PRODUIT_ACTIF_DATE ON produit (actif, date_creation DESC)');
        $this->addSql('CREATE INDEX IDX_PRODUIT_ACTIF_NOM ON produit (actif, nom)');
        $this->addSql('CREATE INDEX IDX_PRODUIT_ACTIF_PRIX ON produit (actif, (COALESCE(prix_promo, prix)))');
        $this->addSql('CREATE INDEX IDX_PRODUIT_ACTIF_STOCK ON produit (actif, stock)');
        $this->addSql('CREATE INDEX IDX_COMMANDE_DATE ON commande (date_creation DESC)');
        $this->addSql('CREATE INDEX IDX_COMMANDE_STATUT_DATE ON commande (statut, date_creation DESC)');
        $this->addSql('CREATE INDEX IDX_COMMANDE_TOTAL ON commande (total)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PRODUIT_ACTIF_DATE');
        $this->addSql('DROP INDEX IDX_PRODUIT_ACTIF_NOM');
        $this->addSql('DROP INDEX IDX_PRODUIT_ACTIF_PRIX');
        $this->addSql('DROP INDEX IDX_PRODUIT_ACTIF_STOCK');
        $this->addSql('DROP INDEX IDX_COMMANDE_DATE');
        $this->addSql('DROP INDEX IDX_COMMANDE_STATUT_DATE');
        $this->addSql('DROP INDEX IDX_COMMANDE_TOTAL');
    }
}
