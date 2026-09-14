<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260914224500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the retired Retailing type_path projection after classification returned to catalog/category identity.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before legacy type-path cleanup.');

        $table = $schema->getTable('retail');
        if (!$table->hasColumn('type_path')) {
            return;
        }

        $this->addSql('DROP INDEX IF EXISTS idx_retail_type_path_kind');
        $this->addSql('ALTER TABLE retail DROP COLUMN type_path');
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('The retired type_path projection is intentionally not recreated.');
    }
}
