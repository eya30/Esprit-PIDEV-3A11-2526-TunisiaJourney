<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416223515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6B7BA4B65F37A13B (token), INDEX IDX_6B7BA4B6A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE programmes CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE programmes ADD CONSTRAINT FK_3631FC3F3BDE73DF FOREIGN KEY (idV) REFERENCES voyages (idV)');
        $this->addSql('ALTER TABLE programmes RENAME INDEX idv TO IDX_3631FC3F3BDE73DF');
        $this->addSql('ALTER TABLE reservationprog DROP FOREIGN KEY reservationprog_ibfk_1');
        $this->addSql('DROP INDEX reservationprog_ibfk_1 ON reservationprog');
        $this->addSql('ALTER TABLE reservationprog ADD statut_paiement VARCHAR(50) DEFAULT NULL, ADD stripe_session_id VARCHAR(255) DEFAULT NULL, DROP statutPaiement, DROP stripeSessionId, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE prixProg prixProg NUMERIC(10, 2) NOT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE mot_de_passe mot_de_passe VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(30) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT NULL, CHANGE niveau niveau VARCHAR(50) DEFAULT NULL, CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT NULL, CHANGE face_token face_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_email ON utilisateur (email)');
        $this->addSql('DROP INDEX id_user ON voyages');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE prix prix NUMERIC(10, 2) NOT NULL, CHANGE dateCreation dateCreation DATE NOT NULL, CHANGE heure heure TIME NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE programmes DROP FOREIGN KEY FK_3631FC3F3BDE73DF');
        $this->addSql('ALTER TABLE programmes CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE programmes RENAME INDEX idx_3631fc3f3bde73df TO idV');
        $this->addSql('ALTER TABLE reservationprog ADD statutPaiement VARCHAR(50) DEFAULT \'\'\'EN_ATTENTE\'\'\', ADD stripeSessionId VARCHAR(255) DEFAULT \'NULL\', DROP statut_paiement, DROP stripe_session_id, CHANGE nom nom VARCHAR(50) NOT NULL, CHANGE prenom prenom VARCHAR(50) NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE prixProg prixProg DOUBLE PRECISION NOT NULL, CHANGE email email VARCHAR(255) DEFAULT \'NULL\', CHANGE user_id user_id INT DEFAULT -1');
        $this->addSql('ALTER TABLE reservationprog ADD CONSTRAINT reservationprog_ibfk_1 FOREIGN KEY (idP) REFERENCES programmes (idProg) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX reservationprog_ibfk_1 ON reservationprog (idP)');
        $this->addSql('DROP INDEX unique_email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE mot_de_passe mot_de_passe VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(30) DEFAULT \'NULL\', CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\', CHANGE adresse adresse VARCHAR(255) DEFAULT \'NULL\', CHANGE date_inscription date_inscription DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'NULL\', CHANGE niveau niveau VARCHAR(50) DEFAULT \'NULL\', CHANGE profile_image_url profile_image_url VARCHAR(500) DEFAULT \'NULL\', CHANGE face_token face_token VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE voyages CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE dateCreation dateCreation DATE DEFAULT \'curdate()\' NOT NULL, CHANGE heure heure TIME DEFAULT \'curtime()\' NOT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX id_user ON voyages (id_user)');
    }
}
