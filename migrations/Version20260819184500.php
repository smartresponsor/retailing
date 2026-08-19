<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260819184500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Canonicalize Retailing ownership with owner_type and owner_id while preserving legacy vendor ownership data';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before ownership canonicalization.');

        $table = $schema->getTable('retail');
        if (!$table->hasColumn('owner_type')) {
            $this->addSql('ALTER TABLE retail ADD owner_type VARCHAR(32) DEFAULT NULL');
        }
        if (!$table->hasColumn('owner_id')) {
            $this->addSql('ALTER TABLE retail ADD owner_id VARCHAR(64) DEFAULT NULL');
        }

        $this->addSql("UPDATE retail SET owner_type = 'vendor' WHERE owner_type IS NULL AND owner_vendor_id IS NOT NULL");
        $this->addSql('UPDATE retail SET owner_id = owner_vendor_id WHERE owner_id IS NULL AND owner_vendor_id IS NOT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_retail_owner_scope_kind ON retail (owner_type, owner_id, kind)');
        $this->addSql('ALTER TABLE retail DROP CONSTRAINT IF EXISTS chk_retail_publishable_state');
        $this->addSql("ALTER TABLE retail ADD CONSTRAINT chk_retail_publishable_state CHECK (object_status <> 'published' OR (owner_type IS NOT NULL AND btrim(owner_type) <> '' AND owner_id IS NOT NULL AND btrim(owner_id) <> '' AND category_id IS NOT NULL AND btrim(category_id) <> '' AND btrim(title) <> ''))");
        $this->addSql("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_retail_owner_type') THEN ALTER TABLE retail ADD CONSTRAINT chk_retail_owner_type CHECK (owner_type IS NULL OR owner_type IN ('vendor', 'access')); END IF; END $$");
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retailing ownership canonicalization is durable production data and intentionally irreversible.');
    }
}
