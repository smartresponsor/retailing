<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260820063000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enforce at most one accepted vendor response per customer retail request';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail_response'), 'Retail response table is required before accepted-response uniqueness can be enforced.');

        $duplicateAccepted = (int) $this->connection->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM (
    SELECT retail_id
    FROM retail_response
    WHERE status = 'accepted'
    GROUP BY retail_id
    HAVING COUNT(*) > 1
) duplicate_accepted
SQL);
        $this->abortIf($duplicateAccepted > 0, 'Existing retail responses contain multiple accepted responses for the same customer request.');

        $indexExists = (bool) $this->connection->fetchOne(<<<'SQL'
SELECT EXISTS (
    SELECT 1
    FROM pg_indexes
    WHERE schemaname = current_schema()
      AND tablename = 'retail_response'
      AND indexname = 'uniq_retail_response_one_accepted'
)
SQL);
        if (!$indexExists) {
            $this->addSql("CREATE UNIQUE INDEX uniq_retail_response_one_accepted ON retail_response (retail_id) WHERE status = 'accepted'");
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Accepted retail response uniqueness is a durable marketplace invariant and intentionally irreversible.');
    }
}
