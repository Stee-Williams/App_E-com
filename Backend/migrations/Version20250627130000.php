<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250627130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du schéma e-commerce NovaShop';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE utilisateur (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, prenom VARCHAR(100) NOT NULL, nom VARCHAR(100) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, roles JSON NOT NULL, jeton_api VARCHAR(64) DEFAULT NULL, jeton_reinitialisation VARCHAR(64) DEFAULT NULL, expiration_jeton_reinitialisation TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_creation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3A76ED395 ON utilisateur (jeton_api)');

        $this->addSql('CREATE TABLE categorie (id SERIAL NOT NULL, nom VARCHAR(120) NOT NULL, slug VARCHAR(150) NOT NULL, description TEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_497DD634989D9B62 ON categorie (slug)');

        $this->addSql('CREATE TABLE produit (id SERIAL NOT NULL, categorie_id INT NOT NULL, nom VARCHAR(200) NOT NULL, slug VARCHAR(220) NOT NULL, description TEXT DEFAULT NULL, prix NUMERIC(10, 2) NOT NULL, prix_promo NUMERIC(10, 2) DEFAULT NULL, stock INT NOT NULL, actif BOOLEAN NOT NULL, image_principale VARCHAR(255) DEFAULT NULL, date_creation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29A5EC27989D9B62 ON produit (slug)');
        $this->addSql('CREATE INDEX IDX_29A5EC27BCF5E72D ON produit (categorie_id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE image_produit (id SERIAL NOT NULL, produit_id INT NOT NULL, chemin VARCHAR(255) NOT NULL, ordre INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8A1B2C3D4E5F6A7B ON image_produit (produit_id)');
        $this->addSql('ALTER TABLE image_produit ADD CONSTRAINT FK_IMAGE_PRODUIT_PRODUIT FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE adresse (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, libelle VARCHAR(100) NOT NULL, rue VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, code_postal VARCHAR(20) NOT NULL, pays VARCHAR(100) NOT NULL, par_defaut BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C33F7837FB88E14F ON adresse (utilisateur_id)');
        $this->addSql('ALTER TABLE adresse ADD CONSTRAINT FK_C33F7837FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE bon_reduction (id SERIAL NOT NULL, code VARCHAR(50) NOT NULL, type VARCHAR(20) NOT NULL, valeur NUMERIC(10, 2) NOT NULL, montant_minimum NUMERIC(10, 2) DEFAULT NULL, date_debut TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_fin TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, utilisations_max INT DEFAULT NULL, utilisations INT NOT NULL, actif BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BON_REDUCTION_CODE ON bon_reduction (code)');

        $this->addSql('CREATE TABLE commande (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, adresse_livraison_id INT DEFAULT NULL, bon_reduction_id INT DEFAULT NULL, statut VARCHAR(30) NOT NULL, sous_total NUMERIC(10, 2) NOT NULL, frais_livraison NUMERIC(10, 2) NOT NULL, reduction NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, numero VARCHAR(20) NOT NULL, date_creation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EEAA67DF55AE19E ON commande (numero)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DFB88E14F ON commande (utilisateur_id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_COMMANDE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_COMMANDE_ADRESSE FOREIGN KEY (adresse_livraison_id) REFERENCES adresse (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_COMMANDE_BON FOREIGN KEY (bon_reduction_id) REFERENCES bon_reduction (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE ligne_commande (id SERIAL NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_LIGNE_COMMANDE_COMMANDE ON ligne_commande (commande_id)');
        $this->addSql('CREATE INDEX IDX_LIGNE_COMMANDE_PRODUIT ON ligne_commande (produit_id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_LIGNE_COMMANDE_COMMANDE FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_LIGNE_COMMANDE_PRODUIT FOREIGN KEY (produit_id) REFERENCES produit (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE avis (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, produit_id INT NOT NULL, note INT NOT NULL, commentaire TEXT DEFAULT NULL, date_creation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX avis_utilisateur_produit ON avis (utilisateur_id, produit_id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8B918D99FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8B918D99F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE element_liste_souhaits (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, produit_id INT NOT NULL, date_ajout TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX liste_souhaits_utilisateur_produit ON element_liste_souhaits (utilisateur_id, produit_id)');
        $this->addSql('ALTER TABLE element_liste_souhaits ADD CONSTRAINT FK_LISTE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE element_liste_souhaits ADD CONSTRAINT FK_LISTE_PRODUIT FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE element_liste_souhaits DROP CONSTRAINT FK_LISTE_PRODUIT');
        $this->addSql('ALTER TABLE element_liste_souhaits DROP CONSTRAINT FK_LISTE_UTILISATEUR');
        $this->addSql('DROP TABLE element_liste_souhaits');
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8B918D99F347EFB');
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8B918D99FB88E14F');
        $this->addSql('DROP TABLE avis');
        $this->addSql('ALTER TABLE ligne_commande DROP CONSTRAINT FK_LIGNE_COMMANDE_PRODUIT');
        $this->addSql('ALTER TABLE ligne_commande DROP CONSTRAINT FK_LIGNE_COMMANDE_COMMANDE');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('ALTER TABLE commande DROP CONSTRAINT FK_COMMANDE_BON');
        $this->addSql('ALTER TABLE commande DROP CONSTRAINT FK_COMMANDE_ADRESSE');
        $this->addSql('ALTER TABLE commande DROP CONSTRAINT FK_COMMANDE_UTILISATEUR');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE bon_reduction');
        $this->addSql('ALTER TABLE adresse DROP CONSTRAINT FK_C33F7837FB88E14F');
        $this->addSql('DROP TABLE adresse');
        $this->addSql('ALTER TABLE image_produit DROP CONSTRAINT FK_IMAGE_PRODUIT_PRODUIT');
        $this->addSql('DROP TABLE image_produit');
        $this->addSql('ALTER TABLE produit DROP CONSTRAINT FK_29A5EC27BCF5E72D');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE utilisateur');
    }
}
