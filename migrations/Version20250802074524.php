<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802074524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device ADD COLUMN created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE device ADD COLUMN updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE device_log ADD COLUMN value VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE device_log ADD COLUMN created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE device_log ADD COLUMN updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__device AS SELECT id, name, ip FROM device');
        $this->addSql('DROP TABLE device');
        $this->addSql('CREATE TABLE device (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, ip VARCHAR(80) NOT NULL)');
        $this->addSql('INSERT INTO device (id, name, ip) SELECT id, name, ip FROM __temp__device');
        $this->addSql('DROP TABLE __temp__device');
        $this->addSql('CREATE TEMPORARY TABLE __temp__device_log AS SELECT id, device_id FROM device_log');
        $this->addSql('DROP TABLE device_log');
        $this->addSql('CREATE TABLE device_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, device_id INTEGER NOT NULL, CONSTRAINT FK_65C1B25C94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO device_log (id, device_id) SELECT id, device_id FROM __temp__device_log');
        $this->addSql('DROP TABLE __temp__device_log');
        $this->addSql('CREATE INDEX IDX_65C1B25C94A4C7D4 ON device_log (device_id)');
    }
}
