<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250629100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Commande invité, variantes produit et notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE variante_produit (id SERIAL NOT NULL, produit_id INT NOT NULL, taille VARCHAR(50) DEFAULT NULL, couleur VARCHAR(50) DEFAULT NULL, stock INT NOT NULL, actif BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_VARIANTE_PRODUIT ON variante_produit (produit_id)');
        $this->addSql('ALTER TABLE variante_produit ADD CONSTRAINT FK_VARIANTE_PRODUIT FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE commande ALTER utilisateur_id DROP NOT NULL');
        $this->addSql('ALTER TABLE commande ADD email_invite VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD prenom_invite VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD nom_invite VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD telephone_invite VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD adresse_invite_libelle VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD adresse_invite_rue VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD adresse_invite_ville VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD adresse_invite_code_postal VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD adresse_invite_pays VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD jeton_suivi VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMMANDE_JETON_SUIVI ON commande (jeton_suivi)');

        $this->addSql('ALTER TABLE ligne_commande ADD variante_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_commande ADD libelle_variante VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_LIGNE_VARIANTE FOREIGN KEY (variante_id) REFERENCES variante_produit (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_LIGNE_VARIANTE ON ligne_commande (variante_id)');

        $this->addSql('CREATE TABLE notification (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, type VARCHAR(30) NOT NULL, titre VARCHAR(150) NOT NULL, message TEXT NOT NULL, lien VARCHAR(255) DEFAULT NULL, lu BOOLEAN NOT NULL, date_creation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_NOTIFICATION_UTILISATEUR ON notification (utilisateur_id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIFICATION_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_NOTIFICATION_UTILISATEUR');
        $this->addSql('DROP TABLE notification');

        $this->addSql('ALTER TABLE ligne_commande DROP CONSTRAINT FK_LIGNE_VARIANTE');
        $this->addSql('DROP INDEX IDX_LIGNE_VARIANTE');
        $this->addSql('ALTER TABLE ligne_commande DROP variante_id');
        $this->addSql('ALTER TABLE ligne_commande DROP libelle_variante');

        $this->addSql('DROP INDEX UNIQ_COMMANDE_JETON_SUIVI');
        $this->addSql('ALTER TABLE commande DROP email_invite');
        $this->addSql('ALTER TABLE commande DROP prenom_invite');
        $this->addSql('ALTER TABLE commande DROP nom_invite');
        $this->addSql('ALTER TABLE commande DROP telephone_invite');
        $this->addSql('ALTER TABLE commande DROP adresse_invite_libelle');
        $this->addSql('ALTER TABLE commande DROP adresse_invite_rue');
        $this->addSql('ALTER TABLE commande DROP adresse_invite_ville');
        $this->addSql('ALTER TABLE commande DROP adresse_invite_code_postal');
        $this->addSql('ALTER TABLE commande DROP adresse_invite_pays');
        $this->addSql('ALTER TABLE commande DROP jeton_suivi');
        $this->addSql('ALTER TABLE commande ALTER utilisateur_id SET NOT NULL');

        $this->addSql('ALTER TABLE variante_produit DROP CONSTRAINT FK_VARIANTE_PRODUIT');
        $this->addSql('DROP TABLE variante_produit');
    }
}
