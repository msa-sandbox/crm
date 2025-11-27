<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127182329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add accountId to leads and contacts, re-think indexes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leads ADD account_id INT NOT NULL AFTER is_deleted');
        $this->addSql('ALTER TABLE contacts ADD account_id INT NOT NULL AFTER is_deleted');
        $this->addSql('ALTER TABLE custom_field_values RENAME INDEX idx_6b64d7ff4d0fdd48 TO IDX_6B64D7FFA1E5E0D4');

        $this->addSql('DROP INDEX idx_contacts_user_active ON contacts');
        $this->addSql('DROP INDEX idx_contacts_user_id ON contacts');
        $this->addSql('CREATE INDEX idx_contacts_account_id ON contacts (account_id)');
        $this->addSql('CREATE INDEX idx_contacts_account_active ON contacts (account_id, is_deleted)');
        $this->addSql('DROP INDEX idx_leads_user_stage_active ON leads');
        $this->addSql('DROP INDEX idx_leads_user_id ON leads');
        $this->addSql('DROP INDEX idx_leads_user_active ON leads');
        $this->addSql('CREATE INDEX idx_leads_account_id ON leads (account_id)');
        $this->addSql('CREATE INDEX idx_leads_account_active ON leads (account_id, is_deleted)');
        $this->addSql('CREATE INDEX idx_leads_account_stage_active ON leads (account_id, pipeline_stage, is_deleted)');

        $this->addSql('CREATE INDEX idx_contacts_account_email_active ON contacts (account_id, email, is_deleted)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leads DROP account_id');
        $this->addSql('ALTER TABLE contacts DROP account_id');
        $this->addSql('ALTER TABLE custom_field_values RENAME INDEX idx_6b64d7ffa1e5e0d4 TO IDX_6B64D7FF4D0FDD48');

        $this->addSql('DROP INDEX idx_leads_account_id ON leads');
        $this->addSql('DROP INDEX idx_leads_account_active ON leads');
        $this->addSql('DROP INDEX idx_leads_account_stage_active ON leads');
        $this->addSql('CREATE INDEX idx_leads_user_stage_active ON leads (user_id, pipeline_stage, is_deleted)');
        $this->addSql('CREATE INDEX idx_leads_user_id ON leads (user_id)');
        $this->addSql('CREATE INDEX idx_leads_user_active ON leads (user_id, is_deleted)');
        $this->addSql('DROP INDEX idx_contacts_account_id ON contacts');
        $this->addSql('DROP INDEX idx_contacts_account_active ON contacts');
        $this->addSql('CREATE INDEX idx_contacts_user_active ON contacts (user_id, is_deleted)');
        $this->addSql('CREATE INDEX idx_contacts_user_id ON contacts (user_id)');

        $this->addSql('DROP INDEX idx_contacts_account_email_active ON contacts');
    }
}
