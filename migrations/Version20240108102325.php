<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240108102325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_token (id INT AUTO_INCREMENT NOT NULL, fresh_user_id INT NOT NULL, send_date DATETIME NOT NULL, expire_date DATETIME NOT NULL, token VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C27AE0B45F37A13B (token), INDEX IDX_C27AE0B445196B6 (fresh_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email_token ADD CONSTRAINT FK_C27AE0B445196B6 FOREIGN KEY (fresh_user_id) REFERENCES fresh_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_token DROP FOREIGN KEY FK_C27AE0B445196B6');
        $this->addSql('DROP TABLE email_token');
    }
}
