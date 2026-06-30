<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626112223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE massage (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, note VARCHAR(255) DEFAULT NULL, position INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE massage_price (id INT AUTO_INCREMENT NOT NULL, minutes INT NOT NULL, price INT NOT NULL, position INT NOT NULL, massage_id INT DEFAULT NULL, INDEX IDX_3668E64DE964225 (massage_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE massage_price ADD CONSTRAINT FK_3668E64DE964225 FOREIGN KEY (massage_id) REFERENCES massage (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE massage_price DROP FOREIGN KEY FK_3668E64DE964225');
        $this->addSql('DROP TABLE massage');
        $this->addSql('DROP TABLE massage_price');
    }
}
