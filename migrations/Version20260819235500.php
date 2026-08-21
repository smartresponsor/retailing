<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260819235500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional selected marketplace service and agreed price profile to retail tasks';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before selection-profile adoption.');

        if (!$schema->getTable('retail')->hasColumn('selection_profile')) {
            $this->addSql('ALTER TABLE retail ADD selection_profile JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retailing selection profile is durable marketplace state and intentionally irreversible.');
    }
}
