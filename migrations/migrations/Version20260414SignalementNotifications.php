<?php
// src/Migrations/Version20260414SignalementNotifications.php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414SignalementNotifications extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Créer table snotifications pour les signalements';
    }

    public function up(Schema $schema): void
    {
        // Créer la table snotifications
        $this->addSql('
            CREATE TABLE IF NOT EXISTS snotifications (
                id INT AUTO_INCREMENT NOT NULL,
                type VARCHAR(50) NOT NULL,
                message LONGTEXT NOT NULL,
                signalement_id INT DEFAULT NULL,
                comment_id INT DEFAULT NULL,
                reason VARCHAR(100) DEFAULT NULL,
                user_reporter_id INT DEFAULT NULL,
                lu TINYINT(1) DEFAULT 0,
                date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_type (type),
                INDEX idx_lu (lu),
                INDEX idx_date (date_creation),
                INDEX idx_signalement (signalement_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        // Ajouter les colonnes à la table signalement
        $this->addSql('ALTER TABLE signalement ADD COLUMN IF NOT EXISTS snotification_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD COLUMN IF NOT EXISTS user_reporter_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD INDEX IF NOT EXISTS idx_snotif (snotification_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS snotifications');
        $this->addSql('ALTER TABLE signalement DROP COLUMN IF EXISTS snotification_id');
        $this->addSql('ALTER TABLE signalement DROP COLUMN IF EXISTS user_reporter_id');
    }
}

// src/Migrations/Version20260415AddTranslationFields.php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415AddTranslationFields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter les champs de traduction à la table commentaire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire ADD original_description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD translated_lang VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD is_translated TINYINT(1) DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP original_description');
        $this->addSql('ALTER TABLE commentaire DROP translated_lang');
        $this->addSql('ALTER TABLE commentaire DROP is_translated');
    }
}