<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507101405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS panier_item (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, INDEX IDX_EBFD0067A76ED395 (user_id), INDEX IDX_EBFD0067F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS snotifications (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, signalement_id INT DEFAULT NULL, comment_id INT DEFAULT NULL, reason VARCHAR(100) DEFAULT NULL, user_reporter_id INT DEFAULT NULL, lu TINYINT(1) DEFAULT 0 NOT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067F347EFB FOREIGN KEY (produit_id) REFERENCES produit (IDPR)');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY fk_favori_act');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY fk_favori_ev');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY fk_favori_user');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE livraison');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE tracking_event');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY fk_activite_evenement');
        $this->addSql('DROP INDEX fk_activite_evenement ON activite');
        $this->addSql('ALTER TABLE activite CHANGE Description description VARCHAR(300) NOT NULL, CHANGE Prix prix DOUBLE PRECISION DEFAULT NULL, CHANGE Image image VARCHAR(255) DEFAULT NULL, CHANGE IDEv evenement_id INT NOT NULL');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_41033743FD02F13 FOREIGN KEY (evenement_id) REFERENCES Evenement (IDEv)');
        $this->addSql('CREATE INDEX IDX_41033743FD02F13 ON activite (evenement_id)');
        $this->addSql('DROP INDEX fk_evenement_utilisateur ON evenement');
        $this->addSql('ALTER TABLE evenement DROP Latitude, DROP Longitude, CHANGE Description description VARCHAR(200) NOT NULL, CHANGE DateDebut DateDebut DATE DEFAULT NULL, CHANGE DateFin DateFin DATE DEFAULT NULL, CHANGE CapaciteMax CapaciteMax INT DEFAULT NULL, CHANGE Image image VARCHAR(200) DEFAULT NULL, CHANGE Organisateur organisateur VARCHAR(50) DEFAULT NULL, CHANGE id id VARCHAR(50) NOT NULL');
        $this->addSql('DROP INDEX idx_reservation_status ON reservationact');
        $this->addSql('DROP INDEX fk_reservation_utilisateur ON reservationact');
        $this->addSql('DROP INDEX fk_reservation_activite ON reservationact');
        $this->addSql('DROP INDEX idx_email ON reservationact');
        $this->addSql('ALTER TABLE reservationact CHANGE id id VARCHAR(50) NOT NULL, CHANGE NombrePlaces NombrePlaces INT DEFAULT NULL, CHANGE Prix prix DOUBLE PRECISION DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'confirmé\' NOT NULL');
        $this->addSql('ALTER TABLE admin_log CHANGE cible cible VARCHAR(255) DEFAULT NULL, CHANGE ip ip VARCHAR(50) DEFAULT NULL');
        // RENAME INDEX not supported on MariaDB 10.4 — use DROP + CREATE
        $this->addSql('ALTER TABLE admin_log DROP INDEX fk_admin_log_acteur');
        $this->addSql('CREATE INDEX IDX_F9383BB0DA6F574A ON admin_log (acteur_id)');
        $this->addSql('ALTER TABLE avis_act DROP FOREIGN KEY avis_act_ibfk_1');
        $this->addSql('DROP INDEX IDAct ON avis_act');
        $this->addSql('ALTER TABLE avis_act CHANGE commentaire commentaire LONGTEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE IDAct activite_id INT NOT NULL');
        $this->addSql('ALTER TABLE avis_act ADD CONSTRAINT FK_A4916D729B0F88B1 FOREIGN KEY (activite_id) REFERENCES Activite (IDAct) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A4916D729B0F88B1 ON avis_act (activite_id)');
        $this->addSql('ALTER TABLE avis_chambre DROP FOREIGN KEY fk_avis_chambre_utilisateur');
        $this->addSql('ALTER TABLE avis_chambre CHANGE commentaire commentaire LONGTEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE date_modification date_modification DATETIME DEFAULT NULL, CHANGE a_ete_modifie a_ete_modifie TINYINT(1) DEFAULT 0 NOT NULL, CHANGE est_publie est_publie TINYINT(1) DEFAULT 1 NOT NULL, CHANGE sentiment sentiment VARCHAR(20) DEFAULT NULL, CHANGE statut_notification statut_notification VARCHAR(20) DEFAULT \'non_lue\' NOT NULL');
        $this->addSql('ALTER TABLE avis_chambre ADD CONSTRAINT FK_EE0A74F9FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        // RENAME INDEX not supported on MariaDB 10.4 — use DROP + CREATE
        $this->addSql('ALTER TABLE avis_chambre DROP INDEX fk_avis_chambre_utilisateur');
        $this->addSql('CREATE INDEX IDX_EE0A74F9FB88E14F ON avis_chambre (utilisateur_id)');
        $this->addSql('ALTER TABLE chambre MODIFY idCh INT NOT NULL');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY fk_chambre_hotel');
        $this->addSql('DROP INDEX fk_chambre_hotel ON chambre');
        $this->addSql('DROP INDEX `primary` ON chambre');
        $this->addSql('ALTER TABLE chambre ADD hotel_id INT DEFAULT NULL, ADD modele3_d_url VARCHAR(500) DEFAULT NULL, DROP idH, DROP modele3D_URL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE idCh id_ch INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT FK_C509E4FF3243BB18 FOREIGN KEY (hotel_id) REFERENCES hotel (idH)');
        $this->addSql('CREATE INDEX IDX_C509E4FF3243BB18 ON chambre (hotel_id)');
        $this->addSql('ALTER TABLE chambre ADD PRIMARY KEY (id_ch)');
        $this->addSql('ALTER TABLE code_promo CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'actif\'');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY fk_commande_user');
        $this->addSql('ALTER TABLE commande CHANGE DateC DateC DATE DEFAULT NULL, CHANGE Statut Statut VARCHAR(30) DEFAULT NULL, CHANGE Total Total NUMERIC(10, 2) DEFAULT NULL, CHANGE AdresseLiv AdresseLiv VARCHAR(255) DEFAULT NULL, CHANGE CodePostal CodePostal VARCHAR(10) DEFAULT NULL, CHANGE ModePaiement ModePaiement VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        // RENAME INDEX not supported on MariaDB 10.4 — use DROP + CREATE
        $this->addSql('ALTER TABLE commande DROP INDEX fk_commande_user');
        $this->addSql('CREATE INDEX IDX_6EEAA67DA76ED395 ON commande (user_id)');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E878DFCAD70');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E878F15803B');
        $this->addSql('DROP INDEX fk_produit ON commande_produit');
        $this->addSql('DROP INDEX IDX_DF1E9E878DFCAD70 ON commande_produit');
        $this->addSql('ALTER TABLE commande_produit CHANGE taille taille VARCHAR(5) DEFAULT NULL, CHANGE IDCO commande_id INT DEFAULT NULL, CHANGE IDPR produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E8782EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (IDCO)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E87F347EFB FOREIGN KEY (produit_id) REFERENCES produit (IDPR)');
        $this->addSql('CREATE INDEX IDX_DF1E9E8782EA2E54 ON commande_produit (commande_id)');
        $this->addSql('CREATE INDEX IDX_DF1E9E87F347EFB ON commande_produit (produit_id)');
        // RENAME INDEX not supported on MariaDB 10.4 — use DROP + CREATE
        $this->addSql('ALTER TABLE commande_produit DROP INDEX user_id');
        $this->addSql('CREATE INDEX IDX_DF1E9E87A76ED395 ON commande_produit (user_id)');
        $this->addSql('DROP INDEX IDX_67F068BCD2BDD6EA ON commentaire');
        $this->addSql('ALTER TABLE commentaire CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE tags tags VARCHAR(255) DEFAULT NULL, CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL, CHANGE ip_address ip_address VARCHAR(45) DEFAULT NULL, CHANGE user_agent user_agent VARCHAR(500) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE translated_lang translated_lang VARCHAR(5) DEFAULT NULL, CHANGE idP publication_id INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (idP)');
        $this->addSql('CREATE INDEX IDX_67F068BC38B217A7 ON commentaire (publication_id)');
        $this->addSql('DROP INDEX unique_utilisateur ON fidelite');
        $this->addSql('ALTER TABLE fidelite CHANGE points points INT DEFAULT 0 NOT NULL, CHANGE niveau niveau VARCHAR(20) DEFAULT \'bronze\' NOT NULL, CHANGE total_depense total_depense NUMERIC(10, 2) DEFAULT \'0\' NOT NULL, CHANGE date_derniere_activite date_derniere_activite DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE forum CHANGE status status VARCHAR(20) DEFAULT \'actif\' NOT NULL');
        $this->addSql('ALTER TABLE hotel MODIFY idH INT NOT NULL');
        $this->addSql('DROP INDEX fk_hotel_utilisateur ON hotel');
        $this->addSql('DROP INDEX `primary` ON hotel');
        $this->addSql('ALTER TABLE hotel CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE promotion promotion DOUBLE PRECISION DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE idH id_h INT AUTO_INCREMENT NOT NULL, CHANGE idUtilisateur id_utilisateur INT NOT NULL');
        $this->addSql('ALTER TABLE hotel ADD PRIMARY KEY (id_h)');
        $this->addSql('ALTER TABLE like_publication ADD CONSTRAINT FK_1CF989F738B217A7 FOREIGN KEY (publication_id) REFERENCES publication (idP)');
        $this->addSql('DROP INDEX idx_email ON liste_attente');
        $this->addSql('DROP INDEX idx_activite_statut ON liste_attente');
        $this->addSql('DROP INDEX idx_statut ON liste_attente');
        $this->addSql('DROP INDEX idx_token ON liste_attente');
        $this->addSql('ALTER TABLE liste_attente CHANGE id_utilisateur id_utilisateur VARCHAR(50) DEFAULT NULL, CHANGE telephone_utilisateur telephone_utilisateur VARCHAR(20) DEFAULT NULL, CHANGE nom_utilisateur nom_utilisateur VARCHAR(100) DEFAULT NULL, CHANGE prenom_utilisateur prenom_utilisateur VARCHAR(100) DEFAULT NULL, CHANGE date_notification date_notification DATETIME DEFAULT NULL, CHANGE date_limite_confirmation date_limite_confirmation DATETIME DEFAULT NULL, CHANGE date_confirmation date_confirmation DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'en_attente\' NOT NULL, CHANGE token_confirmation token_confirmation VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit CHANGE Titre Titre VARCHAR(100) DEFAULT NULL, CHANGE Description Description VARCHAR(255) DEFAULT NULL, CHANGE Disponibilite Disponibilite TINYINT(1) DEFAULT 1 NOT NULL, CHANGE Image Image VARCHAR(255) DEFAULT NULL, CHANGE Prix Prix DOUBLE PRECISION DEFAULT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE Categorie Categorie VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        // RENAME INDEX not supported on MariaDB 10.4 — use DROP + CREATE
        $this->addSql('ALTER TABLE produit DROP INDEX fk_produit_user');
        $this->addSql('CREATE INDEX IDX_29A5EC27A76ED395 ON produit (user_id)');
        $this->addSql('ALTER TABLE programmes DROP FOREIGN KEY FK_3631FC3F3BDE73DF');
        $this->addSql('DROP INDEX idV ON programmes');
        $this->addSql('ALTER TABLE programmes CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE idV voyage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE programmes ADD CONSTRAINT FK_3631FC3F68C9E5AF FOREIGN KEY (voyage_id) REFERENCES voyages (idV)');
        $this->addSql('CREATE INDEX IDX_3631FC3F68C9E5AF ON programmes (voyage_id)');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779266963BB');
        $this->addSql('DROP INDEX IDX_AF3C6779266963BB ON publication');
        $this->addSql('ALTER TABLE publication CHANGE Description Description VARCHAR(200) DEFAULT NULL, CHANGE nom nom VARCHAR(100) DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE video video VARCHAR(500) DEFAULT NULL, CHANGE idF forum_id INT NOT NULL');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677929CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (idF)');
        $this->addSql('CREATE INDEX IDX_AF3C677929CCBAD0 ON publication (forum_id)');
        $this->addSql('ALTER TABLE reservation_chambre MODIFY idRes INT NOT NULL');
        $this->addSql('DROP INDEX fk_reservation_utilisateur ON reservation_chambre');
        $this->addSql('DROP INDEX fk_reservation_chambre ON reservation_chambre');
        $this->addSql('DROP INDEX `primary` ON reservation_chambre');
        $this->addSql('ALTER TABLE reservation_chambre ADD id_utilisateur INT NOT NULL, ADD id_ch INT NOT NULL, ADD date_debut DATE NOT NULL, ADD date_fin DATE NOT NULL, ADD nb_nuit INT NOT NULL, ADD nb_personnes INT NOT NULL, ADD details_prix VARCHAR(255) NOT NULL, ADD date_annulation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD montant_rembourse NUMERIC(10, 2) DEFAULT NULL, DROP idUtilisateur, DROP idCh, DROP dateDebut, DROP dateFin, DROP nbNuit, DROP nbPersonnes, DROP detailsPrix, DROP dateAnnulation, DROP montantRembourse, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE nom nom VARCHAR(100) DEFAULT NULL, CHANGE prenom prenom VARCHAR(100) DEFAULT NULL, CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE avis_envoye avis_envoye TINYINT(1) DEFAULT 0 NOT NULL, CHANGE token_avis token_avis VARCHAR(100) DEFAULT NULL, CHANGE idRes id INT AUTO_INCREMENT NOT NULL, CHANGE prixTotal prix_total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE reservation_chambre ADD PRIMARY KEY (id)');
        // RENAME INDEX not supported on MariaDB 10.4 — use DROP + CREATE
        $this->addSql('ALTER TABLE reservation_chambre DROP INDEX token_avis');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A29C5F7AED959E92 ON reservation_chambre (token_avis)');
        $this->addSql('ALTER TABLE reservationprog DROP FOREIGN KEY reservationprog_ibfk_1');
        $this->addSql('DROP INDEX reservationprog_ibfk_1 ON reservationprog');
        $this->addSql('ALTER TABLE reservationprog CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE prixProg prixProg NUMERIC(10, 2) DEFAULT NULL, CHANGE dateProgramme dateProgramme DATETIME NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE statutPaiement statutPaiement VARCHAR(50) DEFAULT NULL, CHANGE stripeSessionId stripeSessionId VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE treated_at treated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE mot_de_passe mot_de_passe VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(30) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT NULL, CHANGE niveau niveau VARCHAR(50) DEFAULT NULL, CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT NULL, CHANGE face_token face_token VARCHAR(255) DEFAULT NULL, CHANGE two_factor_secret two_factor_secret VARCHAR(255) DEFAULT NULL, CHANGE is_totp_enabled is_totp_enabled TINYINT(1) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_email ON utilisateur (email)');
        $this->addSql('DROP INDEX id_user ON voyages');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE prix prix NUMERIC(10, 2) NOT NULL, CHANGE dateCreation dateCreation DATE NOT NULL, CHANGE heure heure TIME NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE favori (idfav INT AUTO_INCREMENT NOT NULL, id INT NOT NULL, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, IDEv INT DEFAULT NULL, IDAct INT DEFAULT NULL, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX fk_favori_ev (IDEv), INDEX fk_favori_act (IDAct), INDEX idx_favori_id (id), UNIQUE INDEX uq_favori (id, type, IDEv, IDAct), PRIMARY KEY(idfav)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE livraison (id INT NOT NULL, commande_id INT NOT NULL, tracking_number VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'\'\'PREPAREE\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notifications (id INT NOT NULL, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, voyage_id INT DEFAULT NULL, voyage_nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, lu TINYINT(1) DEFAULT 0, date_creation DATETIME DEFAULT \'current_timestamp()\') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tracking_event (id INT NOT NULL, livraison_id INT NOT NULL, event_time DATETIME DEFAULT \'current_timestamp()\' NOT NULL, statut VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, commentaire VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, latitude DOUBLE PRECISION DEFAULT \'NULL\', longitude DOUBLE PRECISION DEFAULT \'NULL\') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT fk_favori_act FOREIGN KEY (IDAct) REFERENCES activite (IDAct) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT fk_favori_ev FOREIGN KEY (IDEv) REFERENCES evenement (IDEv) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT fk_favori_user FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067A76ED395');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067F347EFB');
        $this->addSql('DROP TABLE panier_item');
        $this->addSql('DROP TABLE snotifications');
        $this->addSql('ALTER TABLE Activite DROP FOREIGN KEY FK_41033743FD02F13');
        $this->addSql('DROP INDEX IDX_41033743FD02F13 ON Activite');
        $this->addSql('ALTER TABLE Activite CHANGE description Description VARCHAR(1000) DEFAULT \'NULL\', CHANGE prix Prix DOUBLE PRECISION DEFAULT \'NULL\', CHANGE image Image VARCHAR(255) DEFAULT \'NULL\', CHANGE evenement_id IDEv INT NOT NULL');
        $this->addSql('ALTER TABLE Activite ADD CONSTRAINT fk_activite_evenement FOREIGN KEY (IDEv) REFERENCES evenement (IDEv) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_activite_evenement ON Activite (IDEv)');
        $this->addSql('ALTER TABLE admin_log CHANGE cible cible VARCHAR(255) DEFAULT \'NULL\', CHANGE ip ip VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE admin_log DROP INDEX IDX_F9383BB0DA6F574A');
        $this->addSql('CREATE INDEX FK_admin_log_acteur ON admin_log (acteur_id)');
        $this->addSql('ALTER TABLE avis_act DROP FOREIGN KEY FK_A4916D729B0F88B1');
        $this->addSql('DROP INDEX IDX_A4916D729B0F88B1 ON avis_act');
        $this->addSql('ALTER TABLE avis_act CHANGE commentaire commentaire TEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE activite_id IDAct INT NOT NULL');
        $this->addSql('ALTER TABLE avis_act ADD CONSTRAINT avis_act_ibfk_1 FOREIGN KEY (IDAct) REFERENCES activite (IDAct) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDAct ON avis_act (IDAct)');
        $this->addSql('ALTER TABLE avis_chambre DROP FOREIGN KEY FK_EE0A74F9FB88E14F');
        $this->addSql('ALTER TABLE avis_chambre CHANGE commentaire commentaire TEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT \'current_timestamp()\', CHANGE date_modification date_modification DATETIME DEFAULT \'NULL\', CHANGE a_ete_modifie a_ete_modifie TINYINT(1) DEFAULT 0, CHANGE est_publie est_publie TINYINT(1) DEFAULT 1, CHANGE sentiment sentiment VARCHAR(20) DEFAULT \'NULL\', CHANGE statut_notification statut_notification VARCHAR(20) DEFAULT \'\'\'non_lue\'\'\'');
        $this->addSql('ALTER TABLE avis_chambre ADD CONSTRAINT fk_avis_chambre_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis_chambre DROP INDEX IDX_EE0A74F9FB88E14F');
        $this->addSql('CREATE INDEX fk_avis_chambre_utilisateur ON avis_chambre (utilisateur_id)');
        $this->addSql('ALTER TABLE chambre MODIFY id_ch INT NOT NULL');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY FK_C509E4FF3243BB18');
        $this->addSql('DROP INDEX IDX_C509E4FF3243BB18 ON chambre');
        $this->addSql('DROP INDEX `PRIMARY` ON chambre');
        $this->addSql('ALTER TABLE chambre ADD idH INT NOT NULL, ADD modele3D_URL VARCHAR(500) DEFAULT \'NULL\', DROP hotel_id, DROP modele3_d_url, CHANGE status status VARCHAR(50) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE id_ch idCh INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT fk_chambre_hotel FOREIGN KEY (idH) REFERENCES hotel (idH) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_chambre_hotel ON chambre (idH)');
        $this->addSql('ALTER TABLE chambre ADD PRIMARY KEY (idCh)');
        $this->addSql('ALTER TABLE code_promo CHANGE description description VARCHAR(255) DEFAULT \'NULL\', CHANGE statut statut VARCHAR(20) DEFAULT \'\'\'actif\'\'\'');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande CHANGE DateC DateC DATE NOT NULL, CHANGE Statut Statut VARCHAR(30) DEFAULT \'NULL\', CHANGE Total Total DOUBLE PRECISION DEFAULT \'NULL\', CHANGE AdresseLiv AdresseLiv VARCHAR(255) DEFAULT \'NULL\', CHANGE CodePostal CodePostal VARCHAR(10) DEFAULT \'NULL\', CHANGE ModePaiement ModePaiement VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT fk_commande_user FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande DROP INDEX IDX_6EEAA67DA76ED395');
        $this->addSql('CREATE INDEX fk_commande_user ON commande (user_id)');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E8782EA2E54');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E87F347EFB');
        $this->addSql('DROP INDEX IDX_DF1E9E8782EA2E54 ON commande_produit');
        $this->addSql('DROP INDEX IDX_DF1E9E87F347EFB ON commande_produit');
        $this->addSql('ALTER TABLE commande_produit CHANGE taille taille VARCHAR(5) DEFAULT \'NULL\', CHANGE commande_id IDCO INT DEFAULT NULL, CHANGE produit_id IDPR INT NOT NULL');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E878DFCAD70 FOREIGN KEY (IDCO) REFERENCES commande (IDCO)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E878F15803B FOREIGN KEY (IDPR) REFERENCES produit (IDPR)');
        $this->addSql('CREATE INDEX fk_produit ON commande_produit (IDPR)');
        $this->addSql('CREATE INDEX IDX_DF1E9E878DFCAD70 ON commande_produit (IDCO)');
        $this->addSql('ALTER TABLE commande_produit DROP INDEX IDX_DF1E9E87A76ED395');
        $this->addSql('CREATE INDEX user_id ON commande_produit (user_id)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('DROP INDEX IDX_67F068BC38B217A7 ON commentaire');
        $this->addSql('ALTER TABLE commentaire CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE tags tags VARCHAR(255) DEFAULT \'NULL\', CHANGE cancelled_at cancelled_at DATETIME DEFAULT \'NULL\', CHANGE ip_address ip_address VARCHAR(45) DEFAULT \'NULL\', CHANGE user_agent user_agent VARCHAR(500) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'NULL\', CHANGE translated_lang translated_lang VARCHAR(5) DEFAULT \'NULL\', CHANGE publication_id idP INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_67F068BCD2BDD6EA ON commentaire (idP)');
        $this->addSql('ALTER TABLE Evenement ADD Latitude NUMERIC(10, 8) DEFAULT \'NULL\', ADD Longitude NUMERIC(11, 8) DEFAULT \'NULL\', CHANGE description Description VARCHAR(1000) DEFAULT \'NULL\', CHANGE DateDebut DateDebut DATE NOT NULL, CHANGE DateFin DateFin DATE NOT NULL, CHANGE CapaciteMax CapaciteMax INT NOT NULL, CHANGE image Image VARCHAR(200) DEFAULT \'NULL\', CHANGE organisateur Organisateur VARCHAR(50) DEFAULT \'NULL\', CHANGE id id INT NOT NULL');
        $this->addSql('CREATE INDEX fk_evenement_utilisateur ON Evenement (id)');
        $this->addSql('ALTER TABLE fidelite CHANGE points points INT DEFAULT 0, CHANGE niveau niveau VARCHAR(20) DEFAULT \'\'\'bronze\'\'\', CHANGE total_depense total_depense NUMERIC(10, 2) DEFAULT \'0.00\', CHANGE date_derniere_activite date_derniere_activite DATETIME DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\'');
        $this->addSql('CREATE UNIQUE INDEX unique_utilisateur ON fidelite (id_utilisateur)');
        $this->addSql('ALTER TABLE forum CHANGE status status VARCHAR(20) DEFAULT \'\'\'actif\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE hotel MODIFY id_h INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON hotel');
        $this->addSql('ALTER TABLE hotel CHANGE adresse adresse VARCHAR(255) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL, CHANGE promotion promotion DOUBLE PRECISION DEFAULT \'0\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE status status VARCHAR(50) DEFAULT \'NULL\', CHANGE id_h idH INT AUTO_INCREMENT NOT NULL, CHANGE id_utilisateur idUtilisateur INT NOT NULL');
        $this->addSql('CREATE INDEX fk_hotel_utilisateur ON hotel (idUtilisateur)');
        $this->addSql('ALTER TABLE hotel ADD PRIMARY KEY (idH)');
        $this->addSql('ALTER TABLE like_publication DROP FOREIGN KEY FK_1CF989F738B217A7');
        $this->addSql('ALTER TABLE liste_attente CHANGE id_utilisateur id_utilisateur VARCHAR(50) DEFAULT \'NULL\', CHANGE telephone_utilisateur telephone_utilisateur VARCHAR(20) DEFAULT \'NULL\', CHANGE nom_utilisateur nom_utilisateur VARCHAR(100) DEFAULT \'NULL\', CHANGE prenom_utilisateur prenom_utilisateur VARCHAR(100) DEFAULT \'NULL\', CHANGE date_notification date_notification DATETIME DEFAULT \'NULL\', CHANGE date_limite_confirmation date_limite_confirmation DATETIME DEFAULT \'NULL\', CHANGE date_confirmation date_confirmation DATETIME DEFAULT \'NULL\', CHANGE statut statut VARCHAR(20) DEFAULT \'\'\'en_attente\'\'\' NOT NULL, CHANGE token_confirmation token_confirmation VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX idx_email ON liste_attente (email_utilisateur)');
        $this->addSql('CREATE INDEX idx_activite_statut ON liste_attente (id_activite, statut)');
        $this->addSql('CREATE INDEX idx_statut ON liste_attente (statut)');
        $this->addSql('CREATE INDEX idx_token ON liste_attente (token_confirmation)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27A76ED395');
        $this->addSql('ALTER TABLE produit CHANGE user_id user_id INT DEFAULT NULL, CHANGE Titre Titre VARCHAR(100) DEFAULT \'NULL\', CHANGE Description Description VARCHAR(255) DEFAULT \'NULL\', CHANGE Disponibilite Disponibilite TINYINT(1) DEFAULT 1, CHANGE Image Image VARCHAR(255) DEFAULT \'NULL\', CHANGE Prix Prix DOUBLE PRECISION DEFAULT \'NULL\', CHANGE Categorie Categorie VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE produit DROP INDEX IDX_29A5EC27A76ED395');
        $this->addSql('CREATE INDEX fk_produit_user ON produit (user_id)');
        $this->addSql('ALTER TABLE programmes DROP FOREIGN KEY FK_3631FC3F68C9E5AF');
        $this->addSql('DROP INDEX IDX_3631FC3F68C9E5AF ON programmes');
        $this->addSql('ALTER TABLE programmes CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE voyage_id idV INT DEFAULT NULL');
        $this->addSql('ALTER TABLE programmes ADD CONSTRAINT FK_3631FC3F3BDE73DF FOREIGN KEY (idV) REFERENCES voyages (idV)');
        $this->addSql('CREATE INDEX idV ON programmes (idV)');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677929CCBAD0');
        $this->addSql('DROP INDEX IDX_AF3C677929CCBAD0 ON publication');
        $this->addSql('ALTER TABLE publication CHANGE Description Description VARCHAR(200) DEFAULT \'NULL\', CHANGE nom nom VARCHAR(100) DEFAULT \'NULL\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE video video VARCHAR(500) DEFAULT \'NULL\', CHANGE forum_id idF INT NOT NULL');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779266963BB FOREIGN KEY (idF) REFERENCES forum (idF)');
        $this->addSql('CREATE INDEX IDX_AF3C6779266963BB ON publication (idF)');
        $this->addSql('ALTER TABLE ReservationAct CHANGE id id INT NOT NULL, CHANGE NombrePlaces NombrePlaces INT NOT NULL, CHANGE prix Prix DOUBLE PRECISION NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'\'\'confirmé\'\'\' NOT NULL');
        $this->addSql('CREATE INDEX idx_reservation_status ON ReservationAct (status)');
        $this->addSql('CREATE INDEX fk_reservation_utilisateur ON ReservationAct (id)');
        $this->addSql('CREATE INDEX fk_reservation_activite ON ReservationAct (IDAct)');
        $this->addSql('CREATE INDEX idx_email ON ReservationAct (email)');
        $this->addSql('ALTER TABLE reservationprog CHANGE nom nom VARCHAR(50) NOT NULL, CHANGE prenom prenom VARCHAR(50) NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE prixProg prixProg DOUBLE PRECISION NOT NULL, CHANGE dateProgramme dateProgramme DATE NOT NULL, CHANGE email email VARCHAR(255) DEFAULT \'NULL\', CHANGE statutPaiement statutPaiement VARCHAR(50) DEFAULT \'\'\'EN_ATTENTE\'\'\', CHANGE stripeSessionId stripeSessionId VARCHAR(255) DEFAULT \'NULL\', CHANGE user_id user_id INT DEFAULT -1');
        $this->addSql('ALTER TABLE reservationprog ADD CONSTRAINT reservationprog_ibfk_1 FOREIGN KEY (idP) REFERENCES programmes (idProg) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX reservationprog_ibfk_1 ON reservationprog (idP)');
        $this->addSql('ALTER TABLE reservation_chambre MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON reservation_chambre');
        $this->addSql('ALTER TABLE reservation_chambre ADD idUtilisateur INT NOT NULL, ADD idCh INT NOT NULL, ADD dateDebut DATE NOT NULL, ADD dateFin DATE NOT NULL, ADD nbNuit INT NOT NULL, ADD nbPersonnes INT NOT NULL, ADD detailsPrix TEXT DEFAULT NULL, ADD dateAnnulation DATETIME DEFAULT \'NULL\', ADD montantRembourse NUMERIC(10, 2) DEFAULT \'NULL\', DROP id_utilisateur, DROP id_ch, DROP date_debut, DROP date_fin, DROP nb_nuit, DROP nb_personnes, DROP details_prix, DROP date_annulation, DROP montant_rembourse, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE statut statut VARCHAR(20) DEFAULT \'\'\'confirmee\'\'\', CHANGE nom nom VARCHAR(100) DEFAULT \'NULL\', CHANGE prenom prenom VARCHAR(100) DEFAULT \'NULL\', CHANGE email email VARCHAR(150) DEFAULT \'NULL\', CHANGE avis_envoye avis_envoye TINYINT(1) DEFAULT 0, CHANGE token_avis token_avis VARCHAR(100) DEFAULT \'NULL\', CHANGE id idRes INT AUTO_INCREMENT NOT NULL, CHANGE prix_total prixTotal NUMERIC(10, 2) NOT NULL');
        $this->addSql('CREATE INDEX fk_reservation_utilisateur ON reservation_chambre (idUtilisateur)');
        $this->addSql('CREATE INDEX fk_reservation_chambre ON reservation_chambre (idCh)');
        $this->addSql('ALTER TABLE reservation_chambre ADD PRIMARY KEY (idRes)');
        $this->addSql('ALTER TABLE reservation_chambre DROP INDEX UNIQ_A29C5F7AED959E92');
        $this->addSql('CREATE UNIQUE INDEX token_avis ON reservation_chambre (token_avis)');
        $this->addSql('ALTER TABLE signalement CHANGE treated_at treated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('DROP INDEX unique_email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE mot_de_passe mot_de_passe VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(30) DEFAULT \'NULL\', CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\', CHANGE adresse adresse VARCHAR(255) DEFAULT \'NULL\', CHANGE date_inscription date_inscription DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'NULL\', CHANGE niveau niveau VARCHAR(50) DEFAULT \'NULL\', CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT \'NULL\', CHANGE face_token face_token VARCHAR(255) DEFAULT \'NULL\', CHANGE two_factor_secret two_factor_secret VARCHAR(255) DEFAULT \'NULL\', CHANGE is_totp_enabled is_totp_enabled TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE dateCreation dateCreation DATE DEFAULT \'curdate()\' NOT NULL, CHANGE heure heure TIME DEFAULT \'curtime()\' NOT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX id_user ON voyages (id_user)');
    }
}