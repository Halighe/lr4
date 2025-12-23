<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223085140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD student_id INT NOT NULL, ADD service_id INT NOT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DCB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DCB944F1A ON payment (student_id)');
        $this->addSql('CREATE INDEX IDX_6D28840DED5CA9E6 ON payment (service_id)');
        $this->addSql('ALTER TABLE student CHANGE user_id user_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DCB944F1A');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DED5CA9E6');
        $this->addSql('DROP INDEX IDX_6D28840DCB944F1A ON payment');
        $this->addSql('DROP INDEX IDX_6D28840DED5CA9E6 ON payment');
        $this->addSql('ALTER TABLE payment DROP student_id, DROP service_id');
        $this->addSql('ALTER TABLE student CHANGE user_id user_id INT NOT NULL');
    }
}
