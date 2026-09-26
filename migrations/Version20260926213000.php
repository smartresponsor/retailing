<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

/**
 * Aligns the persisted Retailing root with the current Objecting entity-native
 * system-field contract and removes the completed legacy owner projection.
 */
final class Version20260926213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Canonicalize Retailing Objecting physical columns and remove the retired owner_vendor_id projection.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Retailing production schema requires PostgreSQL.',
        );
        $this->abortIf(!$schema->hasTable('retail_listing'), 'Canonical retail_listing table is required before system-field normalization.');

        $table = $schema->getTable('retail_listing');
        foreach ([
            'object_code' => 'code',
            'object_active' => 'active',
            'object_enabled' => 'enabled',
            'object_status' => 'status',
            'object_created_at' => 'created_at',
            'object_modified_at' => 'modified_at',
            'object_created_by' => 'created_by',
            'object_modified_by' => 'modified_by',
        ] as $legacy => $canonical) {
            $this->abortIf(!$table->hasColumn($legacy), sprintf('Retailing legacy system column "%s" is required for canonical rename.', $legacy));
            $this->abortIf($table->hasColumn($canonical), sprintf('Retailing canonical system column "%s" already exists; refusing ambiguous rename.', $canonical));
        }

        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_code TO code');
        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_active TO active');
        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_enabled TO enabled');
        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_status TO status');
        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_created_at TO created_at');
        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_modified_at TO modified_at');
        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_created_by TO created_by');
        $this->addSql('ALTER TABLE retail_listing RENAME COLUMN object_modified_by TO modified_by');

        if ($table->hasColumn('owner_vendor_id')) {
            $this->addSql("DO $$ BEGIN IF EXISTS (SELECT 1 FROM retail_listing WHERE owner_vendor_id IS NOT NULL AND owner_id IS NULL) THEN RAISE EXCEPTION 'Retailing owner_vendor_id contains values not migrated to owner_id'; END IF; END $$");
            $this->addSql('DROP INDEX IF EXISTS idx_retail_owner_kind');
            $this->addSql('ALTER TABLE retail_listing DROP COLUMN owner_vendor_id');
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retailing system-field normalization removes a retired ownership projection and is intentionally irreversible.');
    }
}
