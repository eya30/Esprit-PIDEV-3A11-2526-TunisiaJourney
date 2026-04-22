<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414181731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE snotifications (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, signalement_id INT DEFAULT NULL, comment_id INT DEFAULT NULL, reason VARCHAR(100) DEFAULT NULL, user_reporter_id INT DEFAULT NULL, lu TINYINT(1) DEFAULT 0 NOT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY fk_activite_evenement');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY fk_chambre_hotel');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY fk_commande_user');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E878F15803B');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E878DFCAD70');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY fk_evenement_utilisateur');
        $this->addSql('ALTER TABLE hotel DROP FOREIGN KEY fk_hotel_utilisateur');
        $this->addSql('ALTER TABLE programmes DROP FOREIGN KEY FK_3631FC3F3BDE73DF');
        $this->addSql('ALTER TABLE reservationact DROP FOREIGN KEY fk_reservation_utilisateur');
        $this->addSql('ALTER TABLE reservationact DROP FOREIGN KEY fk_reservation_activite');
        $this->addSql('ALTER TABLE reservationprog DROP FOREIGN KEY reservationprog_ibfk_1');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE chambre');
        $this->addSql('DROP TABLE code_promo');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_produit');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE hotel');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE programmes');
        $this->addSql('DROP TABLE reservationact');
        $this->addSql('DROP TABLE reservationprog');
        $this->addSql('DROP TABLE reservation_chambre');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE voyages');
        $this->addSql('DROP INDEX idx_type_target ON signalement');
        $this->addSql('DROP INDEX idx_is_treated ON signalement');
        $this->addSql('ALTER TABLE signalement ADD description LONGTEXT DEFAULT NULL, ADD snotification_id INT DEFAULT NULL, ADD user_reporter_id INT DEFAULT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE target_id target_id INT NOT NULL, CHANGE reason reason VARCHAR(50) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE is_treated is_treated TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activite (IDAct INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description VARCHAR(1000) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, TypeActivite VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, HeureDebut VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Duree VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, NomAnimateur VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, CapaciteM INT DEFAULT NULL, Prix DOUBLE PRECISION DEFAULT NULL, Image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, IDEv INT NOT NULL, INDEX fk_activite_evenement (IDEv), PRIMARY KEY(IDAct)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE chambre (idCh INT AUTO_INCREMENT NOT NULL, num INT NOT NULL, type VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prix_nuit DOUBLE PRECISION NOT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, capacite_max INT NOT NULL, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, idH INT NOT NULL, modele3D_URL VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_chambre_hotel (idH), PRIMARY KEY(idCh)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE code_promo (idcode INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, pourcentage_reduction INT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'actif\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY(idcode)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande (user_id INT DEFAULT NULL, IDCO INT AUTO_INCREMENT NOT NULL, Quantite INT DEFAULT NULL, DateC DATE NOT NULL, Statut VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Total DOUBLE PRECISION DEFAULT NULL, AdresseLiv VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, CodePostal VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ModePaiement VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_commande_user (user_id), PRIMARY KEY(IDCO)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande_produit (IDCO INT NOT NULL, IDPR INT NOT NULL, Quantite INT DEFAULT 1 NOT NULL, INDEX fk_produit (IDPR), INDEX IDX_DF1E9E878DFCAD70 (IDCO), PRIMARY KEY(IDCO, IDPR)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE evenement (id INT NOT NULL, IDEv INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description VARCHAR(1000) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, DateDebut DATE NOT NULL, DateFin DATE NOT NULL, Lieu VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, CapaciteMax INT NOT NULL, Image VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Organisateur VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_evenement_utilisateur (id), PRIMARY KEY(IDEv)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE hotel (idH INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, ville VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, adresse VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, etoiles INT DEFAULT NULL, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, promotion DOUBLE PRECISION DEFAULT \'0\', image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, idUtilisateur INT NOT NULL, INDEX fk_hotel_utilisateur (idUtilisateur), PRIMARY KEY(idH)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, voyage_id INT DEFAULT NULL, voyage_nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, lu TINYINT(1) DEFAULT 0, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, expiration DATETIME NOT NULL, used TINYINT(1) DEFAULT 0, INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE produit (IDPR INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Stock INT DEFAULT NULL, Poids INT DEFAULT NULL, Disponibilite TINYINT(1) DEFAULT 1, Image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Prix DOUBLE PRECISION DEFAULT NULL, user_id INT DEFAULT NULL, Categorie VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_produit_user (user_id), PRIMARY KEY(IDPR)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE programmes (idProg VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, dateDebut DATE NOT NULL, dateFin DATE NOT NULL, lieu VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, activiteAssociee VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, hotel VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, idV INT DEFAULT NULL, INDEX idV (idV), PRIMARY KEY(idProg)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservationact (id INT NOT NULL, IDRes INT AUTO_INCREMENT NOT NULL, IDAct INT NOT NULL, Nom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Prenom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, DateReservation DATE NOT NULL, NombrePlaces INT NOT NULL, Prix DOUBLE PRECISION NOT NULL, email VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX idx_email (email), INDEX fk_reservation_utilisateur (id), INDEX fk_reservation_activite (IDAct), PRIMARY KEY(IDRes)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservationprog (idRP INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, nbre INT NOT NULL, prixProg DOUBLE PRECISION NOT NULL, idP VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, dateProgramme DATE NOT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, statutPaiement VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'EN_ATTENTE\' COLLATE `utf8mb4_general_ci`, stripeSessionId VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, user_id INT DEFAULT -1, INDEX reservationprog_ibfk_1 (idP), PRIMARY KEY(idRP)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservation_chambre (idRes INT AUTO_INCREMENT NOT NULL, idUtilisateur INT NOT NULL, idCh INT NOT NULL, dateDebut DATE NOT NULL, dateFin DATE NOT NULL, nbNuit INT NOT NULL, prixTotal NUMERIC(10, 2) NOT NULL, nbPersonnes INT NOT NULL, detailsPrix TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'confirmee\' COLLATE `utf8mb4_general_ci`, dateAnnulation DATETIME DEFAULT NULL, montantRembourse NUMERIC(10, 2) DEFAULT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_reservation_utilisateur (idUtilisateur), INDEX fk_reservation_chambre (idCh), PRIMARY KEY(idRes)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, mot_de_passe VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_naissance DATE DEFAULT NULL, adresse VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, role VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, niveau VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, profile_image_url VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, face_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE voyages (idV INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, capacite INT NOT NULL, prix DOUBLE PRECISION NOT NULL, dateCreation DATE DEFAULT CURRENT_DATE NOT NULL, heure TIME DEFAULT CURRENT_TIME NOT NULL, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, id_user INT NOT NULL, INDEX id_user (id_user), PRIMARY KEY(idV)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT fk_activite_evenement FOREIGN KEY (IDEv) REFERENCES evenement (IDEv) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT fk_chambre_hotel FOREIGN KEY (idH) REFERENCES hotel (idH) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT fk_commande_user FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E878F15803B FOREIGN KEY (IDPR) REFERENCES produit (IDPR)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E878DFCAD70 FOREIGN KEY (IDCO) REFERENCES commande (IDCO)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT fk_evenement_utilisateur FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hotel ADD CONSTRAINT fk_hotel_utilisateur FOREIGN KEY (idUtilisateur) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE programmes ADD CONSTRAINT FK_3631FC3F3BDE73DF FOREIGN KEY (idV) REFERENCES voyages (idV)');
        $this->addSql('ALTER TABLE reservationact ADD CONSTRAINT fk_reservation_utilisateur FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservationact ADD CONSTRAINT fk_reservation_activite FOREIGN KEY (IDAct) REFERENCES activite (IDAct) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservationprog ADD CONSTRAINT reservationprog_ibfk_1 FOREIGN KEY (idP) REFERENCES programmes (idProg) ON DELETE CASCADE');
        $this->addSql('DROP TABLE snotifications');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE signalement DROP description, DROP snotification_id, DROP user_reporter_id, CHANGE type type VARCHAR(20) NOT NULL COMMENT \'video | image | comment\', CHANGE target_id target_id INT NOT NULL COMMENT \'idP pour video/image, idC pour commentaire\', CHANGE reason reason VARCHAR(50) NOT NULL COMMENT \'sexual | violent | hatred | dangerous | spam | child\', CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE is_treated is_treated TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'0=en attente, 1=traité\'');
        $this->addSql('CREATE INDEX idx_type_target ON signalement (type, target_id)');
        $this->addSql('CREATE INDEX idx_is_treated ON signalement (is_treated)');
    }
}
