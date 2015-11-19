<?php

namespace Claroline\TranslatorBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/11/19 06:44:53
 */
class Version20151119184451 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro__git_translation_item (
                id INT AUTO_INCREMENT NOT NULL, 
                subject_id INT DEFAULT NULL, 
                translation_key LONGTEXT NOT NULL, 
                domain LONGTEXT NOT NULL, 
                commit_hash LONGTEXT NOT NULL, 
                lang LONGTEXT NOT NULL, 
                vendor LONGTEXT NOT NULL, 
                bundle LONGTEXT NOT NULL, 
                user_lock TINYINT(1) NOT NULL, 
                admin_lock TINYINT(1) NOT NULL, 
                INDEX IDX_8580639123EDC87 (subject_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE claro__git_lang (
                id INT AUTO_INCREMENT NOT NULL, 
                name LONGTEXT NOT NULL, 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE claro__git_translation (
                id INT AUTO_INCREMENT NOT NULL, 
                translation_item_id INT NOT NULL, 
                creator_id INT DEFAULT NULL, 
                translation LONGTEXT NOT NULL, 
                creation_date DATETIME NOT NULL, 
                INDEX IDX_AD1B59AB363ABAC (translation_item_id), 
                INDEX IDX_AD1B59A61220EA6 (creator_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE claro__git_translation_item 
            ADD CONSTRAINT FK_8580639123EDC87 FOREIGN KEY (subject_id) 
            REFERENCES claro_forum_subject (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro__git_translation 
            ADD CONSTRAINT FK_AD1B59AB363ABAC FOREIGN KEY (translation_item_id) 
            REFERENCES claro__git_translation_item (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro__git_translation 
            ADD CONSTRAINT FK_AD1B59A61220EA6 FOREIGN KEY (creator_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro__git_translation 
            DROP FOREIGN KEY FK_AD1B59AB363ABAC
        ");
        $this->addSql("
            DROP TABLE claro__git_translation_item
        ");
        $this->addSql("
            DROP TABLE claro__git_lang
        ");
        $this->addSql("
            DROP TABLE claro__git_translation
        ");
    }
}