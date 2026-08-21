<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260817141000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add durable listing location profile to retail records';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before listing location profile can be adopted.');

        $table = $schema->getTable('retail');
        if (!$table->hasColumn('location_profile')) {
            $this->addSql('ALTER TABLE retail ADD location_profile JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retail listing location profile is durable production data and is intentionally irreversible.');
    }
}
