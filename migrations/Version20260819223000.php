<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260819223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional marketplace availability profile to retail records';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before availability adoption.');

        if (!$schema->getTable('retail')->hasColumn('availability_profile')) {
            $this->addSql('ALTER TABLE retail ADD availability_profile JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retailing availability profile is durable production data and intentionally irreversible.');
    }
}
