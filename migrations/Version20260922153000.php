<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional effective publication windows to Retailing listings.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before publication scheduling.');

        $this->addSql('ALTER TABLE retail ADD COLUMN IF NOT EXISTS publication_starts_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE retail ADD COLUMN IF NOT EXISTS publication_ends_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_retail_publication_window ON retail (publication_starts_at, publication_ends_at)');
        $this->addSql('ALTER TABLE retail DROP CONSTRAINT IF EXISTS chk_retail_publication_window');
        $this->addSql('ALTER TABLE retail ADD CONSTRAINT chk_retail_publication_window CHECK (publication_starts_at IS NULL OR publication_ends_at IS NULL OR publication_ends_at > publication_starts_at)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->addSql('ALTER TABLE retail DROP CONSTRAINT IF EXISTS chk_retail_publication_window');
        $this->addSql('DROP INDEX IF EXISTS idx_retail_publication_window');
        $this->addSql('ALTER TABLE retail DROP COLUMN IF EXISTS publication_ends_at');
        $this->addSql('ALTER TABLE retail DROP COLUMN IF EXISTS publication_starts_at');
    }
}
