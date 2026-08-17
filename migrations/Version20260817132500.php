<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260817132500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add durable listing fulfillment and pricing profiles to retail records';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before placement profiles can be adopted.');

        $table = $schema->getTable('retail');
        if (!$table->hasColumn('fulfillment_profile')) {
            $this->addSql('ALTER TABLE retail ADD fulfillment_profile JSON DEFAULT NULL');
        }
        if (!$table->hasColumn('pricing_profile')) {
            $this->addSql('ALTER TABLE retail ADD pricing_profile JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retail placement profiles are durable production data and are intentionally irreversible.');
    }
}
