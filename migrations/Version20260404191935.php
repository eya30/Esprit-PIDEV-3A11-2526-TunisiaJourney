<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404191935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY fk_activite_evenement');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY fk_chambre_hotel');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY fk_commande_user');
        $this->addSql('ALTER TABLE livraison DROP FOREIGN KEY fk_livraison_commande');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY password_reset_token_ibfk_1');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY fk_produit_user');
        $this->addSql('ALTER TABLE tracking_event DROP FOREIGN KEY fk_event_livraison');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE chambre');
        $this->addSql('DROP TABLE code_promo');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_produit');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE forum');
        $this->addSql('DROP TABLE hotel');
        $this->addSql('DROP TABLE likes');
        $this->addSql('DROP TABLE livraison');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE reservationact');
        $this->addSql('DROP TABLE reservation_chambre');
        $this->addSql('DROP TABLE tracking_event');
        $this->addSql('ALTER TABLE programmes CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL, CHANGE activiteAssociee activiteAssociee VARCHAR(255) NOT NULL, CHANGE hotel hotel VARCHAR(255) NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE idV idV INT DEFAULT NULL');
        $this->addSql('ALTER TABLE programmes ADD CONSTRAINT FK_3631FC3F3BDE73DF FOREIGN KEY (idV) REFERENCES voyages (idV)');
        $this->addSql('ALTER TABLE programmes RENAME INDEX idv TO IDX_3631FC3F3BDE73DF');
        $this->addSql('ALTER TABLE reservationprog DROP FOREIGN KEY reservationprog_ibfk_1');
        $this->addSql('DROP INDEX reservationprog_ibfk_1 ON reservationprog');
        $this->addSql('ALTER TABLE reservationprog ADD statut_paiement VARCHAR(50) DEFAULT NULL, ADD stripe_session_id VARCHAR(255) DEFAULT NULL, DROP statutPaiement, DROP stripeSessionId, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE prixProg prixProg NUMERIC(10, 2) NOT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE telephone telephone VARCHAR(30) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE niveau niveau VARCHAR(50) DEFAULT NULL, CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT NULL, CHANGE face_token face_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX id_user ON voyages');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE prix prix NUMERIC(10, 2) NOT NULL, CHANGE dateCreation dateCreation DATE NOT NULL, CHANGE heure heure TIME NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activite (IDAct INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description VARCHAR(1000) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, TypeActivite VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, HeureDebut VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Duree VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, NomAnimateur VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, CapaciteM INT NOT NULL, Prix DOUBLE PRECISION NOT NULL, Image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, IDEv INT NOT NULL, INDEX fk_activite_evenement (IDEv), PRIMARY KEY(IDAct)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE chambre (idCh INT AUTO_INCREMENT NOT NULL, num INT NOT NULL, type VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prix_nuit DOUBLE PRECISION NOT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, capacite_max INT NOT NULL, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, idH INT NOT NULL, modele3D_URL VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, INDEX fk_chambre_hotel (idH), PRIMARY KEY(idCh)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE code_promo (idcode INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, pourcentage_reduction INT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'\'\'actif\'\'\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY(idcode)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande (user_id INT NOT NULL, IDCO INT AUTO_INCREMENT NOT NULL, Quantite INT NOT NULL, DateC DATE NOT NULL, Statut VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Total DOUBLE PRECISION DEFAULT \'NULL\', AdresseLiv VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, CodePostal VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, ModePaiement VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, INDEX fk_commande_user (user_id), PRIMARY KEY(IDCO)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande_produit (IDCO INT NOT NULL, IDPR INT NOT NULL, Quantite INT NOT NULL, INDEX fk_produit (IDPR), PRIMARY KEY(IDCO, IDPR)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commentaire (idC INT AUTO_INCREMENT NOT NULL, description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, date_creation DATE NOT NULL, idP INT NOT NULL, id INT NOT NULL, tags VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, INDEX id (id), INDEX idP (idP), PRIMARY KEY(idC)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE evenement (IDEv INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description VARCHAR(1000) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, DateDebut DATE NOT NULL, DateFin DATE NOT NULL, Lieu VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, CapaciteMax INT NOT NULL, Image VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Organisateur VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, id INT NOT NULL, INDEX fk_evenement_utilisateur (id), PRIMARY KEY(IDEv)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forum (idF INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, theme VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(idF)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE hotel (idH INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, ville VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, adresse VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, etoiles INT DEFAULT NULL, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, promotion DOUBLE PRECISION DEFAULT \'0\', image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, idUtilisateur INT NOT NULL, INDEX fk_hotel_utilisateur (idUtilisateur), PRIMARY KEY(idH)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE likes (id INT AUTO_INCREMENT NOT NULL, publication_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_action DATETIME NOT NULL, INDEX publication_id (publication_id), INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE livraison (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, tracking_number VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'\'\'PREPAREE\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX tracking_number (tracking_number), INDEX fk_livraison_commande (commande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, voyage_id INT DEFAULT NULL, voyage_nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, lu TINYINT(1) DEFAULT 0, date_creation DATETIME DEFAULT \'current_timestamp()\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, expiration DATETIME NOT NULL, used TINYINT(1) DEFAULT 0, INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE produit (user_id INT NOT NULL, IDPR INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Stock INT DEFAULT NULL, Poids INT DEFAULT NULL, Disponibilite TINYINT(1) DEFAULT 1, Image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Prix DOUBLE PRECISION DEFAULT \'NULL\', INDEX fk_produit_user (user_id), PRIMARY KEY(IDPR)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE publication (idP INT AUTO_INCREMENT NOT NULL, Description VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, date_creation DATE NOT NULL, idF INT NOT NULL, id INT NOT NULL, INDEX idF (idF), INDEX id (id), PRIMARY KEY(idP)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservationact (IDRes INT AUTO_INCREMENT NOT NULL, id INT NOT NULL, IDAct INT NOT NULL, Nom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Prenom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, DateReservation DATE NOT NULL, NombrePlaces INT NOT NULL, Prix DOUBLE PRECISION NOT NULL, email VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_reservation_utilisateur (id), INDEX fk_reservation_activite (IDAct), PRIMARY KEY(IDRes)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservation_chambre (idRes INT AUTO_INCREMENT NOT NULL, idUtilisateur INT NOT NULL, idCh INT NOT NULL, dateDebut DATE NOT NULL, dateFin DATE NOT NULL, nbNuit INT NOT NULL, prixTotal NUMERIC(10, 2) NOT NULL, nbPersonnes INT NOT NULL, detailsPrix TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'\'\'confirmee\'\'\' COLLATE `utf8mb4_general_ci`, dateAnnulation DATETIME DEFAULT \'NULL\', montantRembourse NUMERIC(10, 2) DEFAULT \'NULL\', INDEX fk_reservation_chambre (idCh), INDEX fk_reservation_utilisateur (idUtilisateur), PRIMARY KEY(idRes)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tracking_event (id INT AUTO_INCREMENT NOT NULL, livraison_id INT NOT NULL, event_time DATETIME DEFAULT \'current_timestamp()\' NOT NULL, statut VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, commentaire VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, latitude DOUBLE PRECISION DEFAULT \'NULL\', longitude DOUBLE PRECISION DEFAULT \'NULL\', INDEX idx_tracking_event_livraison_time (livraison_id, event_time), INDEX IDX_D0F2130A8E54FB25 (livraison_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT fk_activite_evenement FOREIGN KEY (IDEv) REFERENCES evenement (IDEv) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT fk_chambre_hotel FOREIGN KEY (idH) REFERENCES hotel (idH) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT fk_commande_user FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE livraison ADD CONSTRAINT fk_livraison_commande FOREIGN KEY (commande_id) REFERENCES commande (IDCO) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT password_reset_token_ibfk_1 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT fk_produit_user FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracking_event ADD CONSTRAINT fk_event_livraison FOREIGN KEY (livraison_id) REFERENCES livraison (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE programmes DROP FOREIGN KEY FK_3631FC3F3BDE73DF');
        $this->addSql('ALTER TABLE programmes CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE lieu lieu VARCHAR(100) NOT NULL, CHANGE activiteAssociee activiteAssociee VARCHAR(100) DEFAULT \'NULL\', CHANGE hotel hotel VARCHAR(100) DEFAULT \'NULL\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE idV idV INT NOT NULL');
        $this->addSql('ALTER TABLE programmes RENAME INDEX idx_3631fc3f3bde73df TO idV');
        $this->addSql('ALTER TABLE reservationprog ADD statutPaiement VARCHAR(50) DEFAULT \'\'\'EN_ATTENTE\'\'\', ADD stripeSessionId VARCHAR(255) DEFAULT \'NULL\', DROP statut_paiement, DROP stripe_session_id, CHANGE nom nom VARCHAR(50) NOT NULL, CHANGE prenom prenom VARCHAR(50) NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE prixProg prixProg DOUBLE PRECISION NOT NULL, CHANGE email email VARCHAR(255) DEFAULT \'NULL\', CHANGE user_id user_id INT DEFAULT -1');
        $this->addSql('ALTER TABLE reservationprog ADD CONSTRAINT reservationprog_ibfk_1 FOREIGN KEY (idP) REFERENCES programmes (idProg) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX reservationprog_ibfk_1 ON reservationprog (idP)');
        $this->addSql('ALTER TABLE utilisateur CHANGE telephone telephone VARCHAR(30) DEFAULT \'NULL\', CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\', CHANGE adresse adresse VARCHAR(255) DEFAULT \'NULL\', CHANGE date_inscription date_inscription DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'\'\'ACTIF\'\'\', CHANGE niveau niveau VARCHAR(50) DEFAULT \'NULL\', CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT \'NULL\', CHANGE face_token face_token VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE dateCreation dateCreation DATE DEFAULT \'curdate()\' NOT NULL, CHANGE heure heure TIME DEFAULT \'curtime()\' NOT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX id_user ON voyages (id_user)');
    }
}
