<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924144500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename the Retailing root table to the canonical retail_ physical prefix.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Retailing production schema requires PostgreSQL.',
        );
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before canonical table rename.');
        $this->abortIf($schema->hasTable('retail_listing'), 'Canonical retail_listing table already exists.');

        $this->addSql('ALTER TABLE retail RENAME TO retail_listing');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Retailing production schema requires PostgreSQL.',
        );
        $this->abortIf(!$schema->hasTable('retail_listing'), 'Canonical retail_listing table is required before rollback.');
        $this->abortIf($schema->hasTable('retail'), 'Legacy retail table already exists.');

        $this->addSql('ALTER TABLE retail_listing RENAME TO retail');
    }
}
