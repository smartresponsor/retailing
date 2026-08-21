<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260821050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add canonical Retailing type paths and hard-cut runtime classification to the retailing catalog.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before canonical type paths can be added.');

        $hasTypePath = $schema->getTable('retail')->hasColumn('type_path');
        $unresolvablePublished = (int) $this->connection->fetchOne($hasTypePath ? <<<'SQL'
SELECT COUNT(*)
FROM retail AS retail
LEFT JOIN category ON retail.category_id ~ '^[0-9]+$' AND category.id = CAST(retail.category_id AS INTEGER)
WHERE retail.object_status = 'published'
  AND (retail.type_path IS NULL OR btrim(retail.type_path) = '')
  AND (category.id IS NULL OR category.depth <= 0)
SQL
            : <<<'SQL'
SELECT COUNT(*)
FROM retail AS retail
LEFT JOIN category ON retail.category_id ~ '^[0-9]+$' AND category.id = CAST(retail.category_id AS INTEGER)
WHERE retail.object_status = 'published'
  AND (category.id IS NULL OR category.depth <= 0)
SQL);
        $this->abortIf($unresolvablePublished > 0, 'Published Retailing records must resolve from legacy classification before typePath hard-cut can complete.');

        if (!$hasTypePath) {
            $this->addSql('ALTER TABLE retail ADD type_path VARCHAR(255) DEFAULT NULL');
        }

        $this->addSql(<<<'SQL'
UPDATE retail AS retail
SET type_path = replace(
        replace(
            CASE
                WHEN category.path::text LIKE catalog.object_code || '.%' THEN substring(category.path::text FROM char_length(catalog.object_code) + 2)
                ELSE category.slug
            END,
            '_', '-'
        ),
        '.', '/'
    ),
    catalog_code = 'retailing'
FROM category
JOIN catalog ON catalog.id = category.catalog_id
WHERE retail.type_path IS NULL
  AND retail.category_id ~ '^[0-9]+$'
  AND category.id = CAST(retail.category_id AS INTEGER)
  AND category.depth > 0
SQL);

        $this->addSql("UPDATE retail SET catalog_code = 'retailing' WHERE type_path IS NOT NULL AND catalog_code IS DISTINCT FROM 'retailing'");

        $indexExists = (bool) $this->connection->fetchOne(<<<'SQL'
SELECT EXISTS (
    SELECT 1
    FROM pg_indexes
    WHERE schemaname = current_schema()
      AND tablename = 'retail'
      AND indexname = 'idx_retail_type_path_kind'
)
SQL);
        if (!$indexExists) {
            $this->addSql('CREATE INDEX idx_retail_type_path_kind ON retail (type_path, kind)');
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Canonical Retailing type paths are durable business classification and intentionally irreversible.');
    }
}
