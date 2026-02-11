<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210202700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coins (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, year INT DEFAULT NULL, denomination VARCHAR(100) DEFAULT NULL, metal VARCHAR(100) DEFAULT NULL, weight_grams NUMERIC(10, 2) DEFAULT NULL, diameter_mm NUMERIC(10, 2) DEFAULT NULL, mintage INT DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE profiles (id BINARY(16) NOT NULL, display_name VARCHAR(255) DEFAULT NULL, avatar_url VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id BINARY(16) NOT NULL, UNIQUE INDEX UNIQ_8B308530A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_collections (id BINARY(16) NOT NULL, acquired_date DATE DEFAULT NULL, `condition` VARCHAR(100) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, custom_image_url VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, user_id BINARY(16) NOT NULL, coin_id BINARY(16) NOT NULL, INDEX IDX_7A3E1A7BA76ED395 (user_id), INDEX IDX_7A3E1A7B84BBDA7 (coin_id), UNIQUE INDEX user_coin_unique (user_id, coin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id BINARY(16) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, facebook_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE profiles ADD CONSTRAINT FK_8B308530A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_collections ADD CONSTRAINT FK_7A3E1A7BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_collections ADD CONSTRAINT FK_7A3E1A7B84BBDA7 FOREIGN KEY (coin_id) REFERENCES coins (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profiles DROP FOREIGN KEY FK_8B308530A76ED395');
        $this->addSql('ALTER TABLE user_collections DROP FOREIGN KEY FK_7A3E1A7BA76ED395');
        $this->addSql('ALTER TABLE user_collections DROP FOREIGN KEY FK_7A3E1A7B84BBDA7');
        $this->addSql('DROP TABLE coins');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE user_collections');
        $this->addSql('DROP TABLE users');
    }
}
