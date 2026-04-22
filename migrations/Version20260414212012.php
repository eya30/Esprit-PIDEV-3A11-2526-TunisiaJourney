<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les colonnes de traduction à la table commentaire
 * 
 * Colonnes ajoutées:
 * - original_description: Sauvegarde du texte original avant traduction
 * - translated_lang: Langue vers laquelle a été traduit le commentaire
 * - is_translated: Booléen indiquant si le commentaire est traduit
 */
final class Version20260414212012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter les champs de traduction à la table commentaire (original_description, translated_lang, is_translated)';
    }

    public function up(Schema $schema): void
    {
        // Ajouter original_description - sauvegarde du texte original
        $this->addSql('ALTER TABLE commentaire ADD original_description LONGTEXT DEFAULT NULL COMMENT "Sauvegarde du texte original avant traduction"');

        // Ajouter translated_lang - la langue vers laquelle a été traduit
        $this->addSql('ALTER TABLE commentaire ADD translated_lang VARCHAR(5) DEFAULT NULL COMMENT "Code de la langue vers laquelle le commentaire a été traduit (ex: en, es, de)"');

        // Ajouter is_translated - booléen indiquant si c\'est traduit
        $this->addSql('ALTER TABLE commentaire ADD is_translated TINYINT(1) DEFAULT 0 COMMENT "1 si le commentaire est actuellement traduit, 0 sinon"');

        // Créer des indices pour améliorer les performances
        $this->addSql('ALTER TABLE commentaire ADD INDEX idx_is_translated (is_translated)');
        $this->addSql('ALTER TABLE commentaire ADD INDEX idx_translated_lang (translated_lang)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les indices
        $this->addSql('ALTER TABLE commentaire DROP INDEX idx_is_translated');
        $this->addSql('ALTER TABLE commentaire DROP INDEX idx_translated_lang');

        // Supprimer les colonnes
        $this->addSql('ALTER TABLE commentaire DROP COLUMN original_description');
        $this->addSql('ALTER TABLE commentaire DROP COLUMN translated_lang');
        $this->addSql('ALTER TABLE commentaire DROP COLUMN is_translated');
    }
}