<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260817104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add durable catalog classification to retail records';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before catalog classification can be adopted.');

        $table = $schema->getTable('retail');
        if (!$table->hasColumn('catalog_code')) {
            $this->addSql('ALTER TABLE retail ADD catalog_code VARCHAR(64) DEFAULT NULL');
        }

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_retail_catalog_category_kind ON retail (catalog_code, category_id, kind)');
        $this->addSql("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_retail_catalog_publishable') THEN ALTER TABLE retail ADD CONSTRAINT chk_retail_catalog_publishable CHECK (object_status <> 'published' OR (catalog_code IS NOT NULL AND btrim(catalog_code) <> '')); END IF; END $$");
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retail catalog classification is durable production data and is intentionally irreversible.');
    }
}
