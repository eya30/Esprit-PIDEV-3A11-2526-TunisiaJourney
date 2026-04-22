<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413144708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY panier_item_ibfk_2');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY panier_item_ibfk_1');
        $this->addSql('DROP TABLE panier_item');
        $this->addSql('ALTER TABLE activite CHANGE prix prix DOUBLE PRECISION DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE activite RENAME INDEX fk_activite_evenement TO IDX_4103374384A382FE');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY fk_evenement_utilisateur');
        $this->addSql('DROP INDEX fk_evenement_utilisateur ON evenement');
        $this->addSql('ALTER TABLE evenement CHANGE id id VARCHAR(50) NOT NULL, CHANGE Description description VARCHAR(200) NOT NULL, CHANGE DateDebut DateDebut DATE DEFAULT NULL, CHANGE DateFin DateFin DATE DEFAULT NULL, CHANGE CapaciteMax CapaciteMax INT DEFAULT NULL, CHANGE Image image VARCHAR(200) DEFAULT NULL, CHANGE Organisateur organisateur VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservationact DROP FOREIGN KEY fk_reservation_utilisateur');
        $this->addSql('ALTER TABLE reservationact DROP FOREIGN KEY fk_reservation_activite');
        $this->addSql('DROP INDEX fk_reservation_utilisateur ON reservationact');
        $this->addSql('DROP INDEX fk_reservation_activite ON reservationact');
        $this->addSql('DROP INDEX idx_email ON reservationact');
        $this->addSql('ALTER TABLE reservationact CHANGE id id VARCHAR(50) NOT NULL, CHANGE NombrePlaces NombrePlaces INT DEFAULT NULL, CHANGE Prix prix DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY fk_commande_user');
        $this->addSql('ALTER TABLE commande CHANGE DateC DateC DATE DEFAULT NULL, CHANGE Statut Statut VARCHAR(30) DEFAULT NULL, CHANGE Total Total DOUBLE PRECISION DEFAULT NULL, CHANGE AdresseLiv AdresseLiv VARCHAR(255) DEFAULT NULL, CHANGE CodePostal CodePostal VARCHAR(10) DEFAULT NULL, CHANGE ModePaiement ModePaiement VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commande RENAME INDEX fk_commande_user TO IDX_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX fk_produit TO IDX_DF1E9E878F15803B');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX user_id TO IDX_DF1E9E87A76ED395');
        $this->addSql('ALTER TABLE produit CHANGE Titre Titre VARCHAR(100) DEFAULT NULL, CHANGE Description Description VARCHAR(255) DEFAULT NULL, CHANGE Disponibilite Disponibilite TINYINT(1) DEFAULT 1 NOT NULL, CHANGE Image Image VARCHAR(255) DEFAULT NULL, CHANGE Prix Prix DOUBLE PRECISION DEFAULT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE Categorie Categorie VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE produit RENAME INDEX fk_produit_user TO IDX_29A5EC27A76ED395');
        $this->addSql('ALTER TABLE programmes CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE programmes RENAME INDEX idv TO IDX_3631FC3F3BDE73DF');
        $this->addSql('ALTER TABLE reservationprog DROP FOREIGN KEY reservationprog_ibfk_1');
        $this->addSql('DROP INDEX reservationprog_ibfk_1 ON reservationprog');
        $this->addSql('ALTER TABLE reservationprog CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE prixProg prixProg NUMERIC(10, 2) DEFAULT NULL, CHANGE dateProgramme dateProgramme DATETIME NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE statutPaiement statutPaiement VARCHAR(50) DEFAULT NULL, CHANGE stripeSessionId stripeSessionId VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE telephone telephone VARCHAR(30) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT NULL, CHANGE niveau niveau VARCHAR(50) DEFAULT NULL, CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT NULL, CHANGE face_token face_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX id_user ON voyages');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE prix prix NUMERIC(10, 2) NOT NULL, CHANGE dateCreation dateCreation DATE NOT NULL, CHANGE heure heure TIME NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE panier_item (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, produit_id INT NOT NULL, quantite INT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, INDEX user_id (user_id), INDEX produit_id (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT panier_item_ibfk_2 FOREIGN KEY (produit_id) REFERENCES produit (IDPR) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT panier_item_ibfk_1 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Activite CHANGE prix prix DOUBLE PRECISION DEFAULT \'NULL\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE Activite RENAME INDEX idx_4103374384a382fe TO fk_activite_evenement');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande CHANGE DateC DateC DATE NOT NULL, CHANGE Statut Statut VARCHAR(30) DEFAULT \'NULL\', CHANGE Total Total DOUBLE PRECISION DEFAULT \'NULL\', CHANGE AdresseLiv AdresseLiv VARCHAR(255) DEFAULT \'NULL\', CHANGE CodePostal CodePostal VARCHAR(10) DEFAULT \'NULL\', CHANGE ModePaiement ModePaiement VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT fk_commande_user FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande RENAME INDEX idx_6eeaa67da76ed395 TO fk_commande_user');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX idx_df1e9e87a76ed395 TO user_id');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX idx_df1e9e878f15803b TO fk_produit');
        $this->addSql('ALTER TABLE Evenement CHANGE description Description VARCHAR(1000) DEFAULT \'NULL\', CHANGE DateDebut DateDebut DATE NOT NULL, CHANGE DateFin DateFin DATE NOT NULL, CHANGE CapaciteMax CapaciteMax INT NOT NULL, CHANGE image Image VARCHAR(200) DEFAULT \'NULL\', CHANGE organisateur Organisateur VARCHAR(50) DEFAULT \'NULL\', CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE Evenement ADD CONSTRAINT fk_evenement_utilisateur FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_evenement_utilisateur ON Evenement (id)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27A76ED395');
        $this->addSql('ALTER TABLE produit CHANGE user_id user_id INT DEFAULT NULL, CHANGE Titre Titre VARCHAR(100) DEFAULT \'NULL\', CHANGE Description Description VARCHAR(255) DEFAULT \'NULL\', CHANGE Disponibilite Disponibilite TINYINT(1) DEFAULT 1, CHANGE Image Image VARCHAR(255) DEFAULT \'NULL\', CHANGE Prix Prix DOUBLE PRECISION DEFAULT \'NULL\', CHANGE Categorie Categorie VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE produit RENAME INDEX idx_29a5ec27a76ed395 TO fk_produit_user');
        $this->addSql('ALTER TABLE programmes CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE programmes RENAME INDEX idx_3631fc3f3bde73df TO idV');
        $this->addSql('ALTER TABLE ReservationAct CHANGE id id INT NOT NULL, CHANGE NombrePlaces NombrePlaces INT NOT NULL, CHANGE prix Prix DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE ReservationAct ADD CONSTRAINT fk_reservation_utilisateur FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ReservationAct ADD CONSTRAINT fk_reservation_activite FOREIGN KEY (IDAct) REFERENCES activite (IDAct) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_reservation_utilisateur ON ReservationAct (id)');
        $this->addSql('CREATE INDEX fk_reservation_activite ON ReservationAct (IDAct)');
        $this->addSql('CREATE INDEX idx_email ON ReservationAct (email)');
        $this->addSql('ALTER TABLE reservationprog CHANGE nom nom VARCHAR(50) NOT NULL, CHANGE prenom prenom VARCHAR(50) NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE prixProg prixProg DOUBLE PRECISION NOT NULL, CHANGE dateProgramme dateProgramme DATE NOT NULL, CHANGE email email VARCHAR(255) DEFAULT \'NULL\', CHANGE statutPaiement statutPaiement VARCHAR(50) DEFAULT \'\'\'EN_ATTENTE\'\'\', CHANGE stripeSessionId stripeSessionId VARCHAR(255) DEFAULT \'NULL\', CHANGE user_id user_id INT DEFAULT -1');
        $this->addSql('ALTER TABLE reservationprog ADD CONSTRAINT reservationprog_ibfk_1 FOREIGN KEY (idP) REFERENCES programmes (idProg) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX reservationprog_ibfk_1 ON reservationprog (idP)');
        $this->addSql('ALTER TABLE utilisateur CHANGE telephone telephone VARCHAR(30) DEFAULT \'NULL\', CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\', CHANGE adresse adresse VARCHAR(255) DEFAULT \'NULL\', CHANGE date_inscription date_inscription DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'NULL\', CHANGE niveau niveau VARCHAR(50) DEFAULT \'NULL\', CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT \'NULL\', CHANGE face_token face_token VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE dateCreation dateCreation DATE DEFAULT \'curdate()\' NOT NULL, CHANGE heure heure TIME DEFAULT \'curtime()\' NOT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX id_user ON voyages (id_user)');
    }
}
