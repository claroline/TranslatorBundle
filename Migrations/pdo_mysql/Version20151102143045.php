<?php

namespace Claroline\TranslatorBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/11/02 02:30:46
 */
class Version20151102143045 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro__git_translation_item (
                id INT AUTO_INCREMENT NOT NULL, 
                translation_key LONGTEXT NOT NULL, 
                translation_value LONGTEXT NOT NULL, 
                domain LONGTEXT NOT NULL, 
                commit_hash LONGTEXT NOT NULL, 
                lang LONGTEXT NOT NULL, 
                creation_date DATETIME NOT NULL, 
                vendor LONGTEXT NOT NULL, 
                bundle LONGTEXT NOT NULL, 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro__git_translation_item
        ");
    }
}