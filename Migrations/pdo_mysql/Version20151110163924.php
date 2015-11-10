<?php

namespace Claroline\TranslatorBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/11/10 04:39:26
 */
class Version20151110163924 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro__git_translation_item (
                id INT AUTO_INCREMENT NOT NULL, 
                creator_id INT DEFAULT NULL, 
                translation_key LONGTEXT NOT NULL, 
                translation_value LONGTEXT NOT NULL, 
                domain LONGTEXT NOT NULL, 
                commit_hash LONGTEXT NOT NULL, 
                lang LONGTEXT NOT NULL, 
                creation_date DATETIME NOT NULL, 
                vendor LONGTEXT NOT NULL, 
                bundle LONGTEXT NOT NULL, 
                user_lock TINYINT(1) NOT NULL, 
                admin_lock TINYINT(1) NOT NULL, 
                INDEX IDX_8580639161220EA6 (creator_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE claro__git_Lang (
                id INT AUTO_INCREMENT NOT NULL, 
                name LONGTEXT NOT NULL, 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE claro__git_translation_item 
            ADD CONSTRAINT FK_8580639161220EA6 FOREIGN KEY (creator_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro__git_translation_item
        ");
        $this->addSql("
            DROP TABLE claro__git_Lang
        ");
    }
}