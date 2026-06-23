<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517182633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intervention ADD priority VARCHAR(50) DEFAULT NULL, ADD device VARCHAR(150) DEFAULT NULL, ADD serial_number VARCHAR(120) DEFAULT NULL, ADD operating_system VARCHAR(120) DEFAULT NULL, ADD replaced_parts LONGTEXT DEFAULT NULL, ADD payment_method VARCHAR(80) DEFAULT NULL, ADD quote_reference VARCHAR(80) DEFAULT NULL, ADD invoice_reference VARCHAR(80) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intervention DROP priority, DROP device, DROP serial_number, DROP operating_system, DROP replaced_parts, DROP payment_method, DROP quote_reference, DROP invoice_reference');
    }
}
