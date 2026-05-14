<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renommage des colonnes en snake_case pour activite et reservationact.';
    }

    public function up(Schema $schema): void
    {
        // -------------------------------------------------------
        // TABLE : activite — renommage des colonnes (pas de FK à supprimer)
        // -------------------------------------------------------
        $this->addSql('ALTER TABLE activite
            CHANGE IDAct        id_act        INT NOT NULL AUTO_INCREMENT,
            CHANGE Titre        titre         VARCHAR(100) NOT NULL,
            CHANGE Description  description   VARCHAR(1000) DEFAULT NULL,
            CHANGE TypeActivite type_activite VARCHAR(50) NOT NULL,
            CHANGE HeureDebut   heure_debut   VARCHAR(20) NOT NULL,
            CHANGE Duree        duree         VARCHAR(20) NOT NULL,
            CHANGE NomAnimateur nom_animateur VARCHAR(100) NOT NULL,
            CHANGE CapaciteM    capacite_m    INT DEFAULT NULL,
            CHANGE Prix         prix          FLOAT DEFAULT NULL,
            CHANGE Image        image         VARCHAR(255) DEFAULT NULL,
            CHANGE IDEv         id_ev         INT NOT NULL
        ');

        // Recréer les FK vers activite avec les nouveaux noms
        $this->addSql('ALTER TABLE activite
            ADD CONSTRAINT fk_activite_evenement
            FOREIGN KEY (id_ev) REFERENCES evenement (id_ev)
            ON DELETE CASCADE
        ');
        $this->addSql('ALTER TABLE avis_act
            ADD CONSTRAINT avis_act_ibfk_1
            FOREIGN KEY (IDAct) REFERENCES activite (id_act)
        ');
        $this->addSql('ALTER TABLE favori
            ADD CONSTRAINT fk_favori_act
            FOREIGN KEY (IDAct) REFERENCES activite (id_act)
        ');
        $this->addSql('ALTER TABLE favori
            ADD CONSTRAINT fk_favori_ev
            FOREIGN KEY (IDEv) REFERENCES evenement (id_ev)
        ');

        // -------------------------------------------------------
        // TABLE : reservationact — renommage des colonnes + renommage table
        // -------------------------------------------------------
        $this->addSql('ALTER TABLE reservationact
            CHANGE IDRes           id_res           INT NOT NULL AUTO_INCREMENT,
            CHANGE id              user_id          INT NOT NULL,
            CHANGE IDAct           id_act           INT NOT NULL,
            CHANGE Nom             nom              VARCHAR(50) NOT NULL,
            CHANGE Prenom          prenom           VARCHAR(50) NOT NULL,
            CHANGE DateReservation date_reservation DATE NOT NULL,
            CHANGE NombrePlaces    nombre_places    INT NOT NULL,
            CHANGE Prix            prix             FLOAT NOT NULL,
            CHANGE email           email            VARCHAR(300) NOT NULL,
            CHANGE telephone       telephone        VARCHAR(8) NOT NULL
        ');
        $this->addSql('RENAME TABLE reservationact TO reservation_act');
    }

    public function down(Schema $schema): void
    {
        // Rollback reservation_act
        $this->addSql('RENAME TABLE reservation_act TO reservationact');
        $this->addSql('ALTER TABLE reservationact
            CHANGE id_res           IDRes           INT NOT NULL AUTO_INCREMENT,
            CHANGE user_id          id              INT NOT NULL,
            CHANGE id_act           IDAct           INT NOT NULL,
            CHANGE nom              Nom             VARCHAR(50) NOT NULL,
            CHANGE prenom           Prenom          VARCHAR(50) NOT NULL,
            CHANGE date_reservation DateReservation DATE NOT NULL,
            CHANGE nombre_places    NombrePlaces    INT NOT NULL,
            CHANGE prix             Prix            FLOAT NOT NULL,
            CHANGE email            email           VARCHAR(300) NOT NULL,
            CHANGE telephone        telephone       VARCHAR(8) NOT NULL
        ');

        // Rollback activite
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY fk_activite_evenement');
        $this->addSql('ALTER TABLE avis_act DROP FOREIGN KEY avis_act_ibfk_1');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY fk_favori_act');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY fk_favori_ev');

        $this->addSql('ALTER TABLE activite
            CHANGE id_act        IDAct        INT NOT NULL AUTO_INCREMENT,
            CHANGE titre         Titre        VARCHAR(100) NOT NULL,
            CHANGE description   Description  VARCHAR(1000) DEFAULT NULL,
            CHANGE type_activite TypeActivite VARCHAR(50) NOT NULL,
            CHANGE heure_debut   HeureDebut   VARCHAR(20) NOT NULL,
            CHANGE duree         Duree        VARCHAR(20) NOT NULL,
            CHANGE nom_animateur NomAnimateur VARCHAR(100) NOT NULL,
            CHANGE capacite_m    CapaciteM    INT DEFAULT NULL,
            CHANGE prix          Prix         FLOAT DEFAULT NULL,
            CHANGE image         Image        VARCHAR(255) DEFAULT NULL,
            CHANGE id_ev         IDEv         INT NOT NULL
        ');
    }
}