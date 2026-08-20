<?php

declare(strict_types=1);

namespace App\Retailing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260820060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vendor responses for customer retail requests';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Retailing production schema requires PostgreSQL.');
        $this->abortIf(!$schema->hasTable('retail'), 'Retail table is required before retail response adoption.');

        if (!$schema->hasTable('retail_response')) {
            $this->addSql('CREATE TABLE retail_response (id SERIAL NOT NULL, retail_id INT NOT NULL, vendor_id VARCHAR(64) NOT NULL, service_id INT DEFAULT NULL, status VARCHAR(16) NOT NULL, description TEXT DEFAULT NULL, pricing_profile JSON DEFAULT NULL, fulfillment_profile JSON DEFAULT NULL, availability_profile JSON DEFAULT NULL, location_profile JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, submitted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX idx_retail_response_retail_status ON retail_response (retail_id, status)');
            $this->addSql('CREATE INDEX idx_retail_response_vendor_status ON retail_response (vendor_id, status)');
            $this->addSql('CREATE UNIQUE INDEX uniq_retail_response_retail_vendor ON retail_response (retail_id, vendor_id)');
            $this->addSql('ALTER TABLE retail_response ADD CONSTRAINT fk_retail_response_retail FOREIGN KEY (retail_id) REFERENCES retail (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

            return;
        }

        $table = $schema->getTable('retail_response');
        foreach (['id', 'retail_id', 'vendor_id', 'status', 'created_at', 'updated_at'] as $requiredColumn) {
            $this->abortIf(!$table->hasColumn($requiredColumn), sprintf('Existing retail_response table is incompatible: missing %s.', $requiredColumn));
        }

        $optionalColumns = [
            'service_id' => 'INT DEFAULT NULL',
            'description' => 'TEXT DEFAULT NULL',
            'pricing_profile' => 'JSON DEFAULT NULL',
            'fulfillment_profile' => 'JSON DEFAULT NULL',
            'availability_profile' => 'JSON DEFAULT NULL',
            'location_profile' => 'JSON DEFAULT NULL',
            'submitted_at' => 'TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL',
            'accepted_at' => 'TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL',
        ];
        foreach ($optionalColumns as $column => $definition) {
            if (!$table->hasColumn($column)) {
                $this->addSql(sprintf('ALTER TABLE retail_response ADD %s %s', $column, $definition));
            }
        }
        if (!$table->hasIndex('idx_retail_response_retail_status')) {
            $this->addSql('CREATE INDEX idx_retail_response_retail_status ON retail_response (retail_id, status)');
        }
        if (!$table->hasIndex('idx_retail_response_vendor_status')) {
            $this->addSql('CREATE INDEX idx_retail_response_vendor_status ON retail_response (vendor_id, status)');
        }
        if (!$table->hasIndex('uniq_retail_response_retail_vendor')) {
            $this->addSql('CREATE UNIQUE INDEX uniq_retail_response_retail_vendor ON retail_response (retail_id, vendor_id)');
        }
        if (!$table->hasForeignKey('fk_retail_response_retail')) {
            $this->addSql('ALTER TABLE retail_response ADD CONSTRAINT fk_retail_response_retail FOREIGN KEY (retail_id) REFERENCES retail (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Retail responses are durable marketplace commercial intent and intentionally irreversible.');
    }
}
