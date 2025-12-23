<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223061218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment_check (id INT AUTO_INCREMENT NOT NULL, payment_id INT NOT NULL, employee_id INT NOT NULL, UNIQUE INDEX UNIQ_6BC79AA14C3A3BB (payment_id), INDEX IDX_6BC79AA18C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment_check ADD CONSTRAINT FK_6BC79AA14C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE payment_check ADD CONSTRAINT FK_6BC79AA18C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_check DROP FOREIGN KEY FK_6BC79AA14C3A3BB');
        $this->addSql('ALTER TABLE payment_check DROP FOREIGN KEY FK_6BC79AA18C03F15C');
        $this->addSql('DROP TABLE payment_check');
    }
}
