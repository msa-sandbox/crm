<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123144757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create CRM tables: leads, contacts, custom_fields and custom_field_values with relations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<SQL
CREATE TABLE contacts
    (
        id          INT AUTO_INCREMENT                     NOT NULL,
        first_name  VARCHAR(100)                           NOT NULL,
        last_name   VARCHAR(100)                           NOT NULL,
        email       VARCHAR(150) DEFAULT NULL,
        phone       VARCHAR(20)  DEFAULT NULL,
        company     VARCHAR(100) DEFAULT NULL,
        city        VARCHAR(100) DEFAULT NULL,
        country     VARCHAR(100) DEFAULT NULL,
        notes       LONGTEXT     DEFAULT NULL,
        is_deleted  TINYINT(1)   DEFAULT 0                 NOT NULL,
        user_id     INT                                    NOT NULL,
        created_by  INT                                    NOT NULL,
        updated_by  INT                                    NOT NULL,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT '(DC2Type:datetime_immutable)',
        updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT '(DC2Type:datetime_immutable)',
        INDEX idx_contacts_user_id (user_id),
        INDEX idx_contacts_user_active (user_id, is_deleted),
        PRIMARY KEY (id)
    ) DEFAULT CHARACTER SET utf8mb4
    COLLATE `utf8mb4_unicode_ci`
    ENGINE = InnoDB;
SQL
        );



        $this->addSql(<<<SQL
CREATE TABLE custom_fields
(
    id          INT AUTO_INCREMENT   NOT NULL,
    user_id     INT                  NOT NULL,
    entity_type VARCHAR(50)          NOT NULL,
    field_key   VARCHAR(100)         NOT NULL,
    label       VARCHAR(150)         NOT NULL,
    field_type  VARCHAR(50)          NOT NULL,
    options     JSON       DEFAULT NULL,
    is_required TINYINT(1) DEFAULT 0 NOT NULL,
    is_editable TINYINT(1) DEFAULT 1 NOT NULL,
    is_system   TINYINT(1) DEFAULT 0 NOT NULL,
    created_at  DATETIME             NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at  DATETIME             NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX idx_cf_user_id (user_id),
    INDEX idx_cf_entity_type (entity_type),
    INDEX idx_cf_user_entity (user_id, entity_type),
    UNIQUE INDEX uniq_user_entity_field (user_id, entity_type, field_key),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB;

SQL
        );



        $this->addSql(<<<SQL
CREATE TABLE custom_field_values
(
    id              INT AUTO_INCREMENT NOT NULL,
    custom_field_id INT                NOT NULL,
    entity_type     VARCHAR(50)        NOT NULL,
    entity_id       INT                NOT NULL,
    value           LONGTEXT DEFAULT NULL,
    created_at      DATETIME           NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at      DATETIME           NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_6B64D7FF4D0FDD48 (custom_field_id),
    INDEX idx_cfv_entity (entity_type, entity_id),
    UNIQUE INDEX uniq_field_entity_value (custom_field_id, entity_type, entity_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB;
SQL
    );



        $this->addSql(<<<SQL
CREATE TABLE leads
(
    id             INT AUTO_INCREMENT                     NOT NULL,
    title          VARCHAR(100)                           NOT NULL,
    status         VARCHAR(50)                            NOT NULL,
    pipeline_stage VARCHAR(50)                            NOT NULL,
    budget         NUMERIC(10, 0)                         NOT NULL,
    description    VARCHAR(200) DEFAULT NULL,
    notes          LONGTEXT     DEFAULT NULL,
    is_deleted     TINYINT(1)   DEFAULT 0                 NOT NULL,
    user_id        INT                                    NOT NULL,
    created_by     INT                                    NOT NULL,
    updated_by     INT                                    NOT NULL,
    created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at     DATETIME     DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX idx_leads_user_id (user_id),
    INDEX idx_leads_user_active (user_id, is_deleted),
    INDEX idx_leads_user_stage_active (user_id, pipeline_stage, is_deleted),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB;
SQL
        );



        $this->addSql(<<<SQL
CREATE TABLE lead_contacts
    (
        lead_id    INT NOT NULL,
        contact_id INT NOT NULL,
        INDEX IDX_9913977055458D (lead_id),
        INDEX IDX_99139770E7A1254A (contact_id),
        PRIMARY KEY (lead_id, contact_id)
    ) DEFAULT CHARACTER SET utf8mb4
COLLATE `utf8mb4_unicode_ci`
ENGINE = InnoDB;
SQL
        );


        $this->addSql('ALTER TABLE custom_field_values ADD CONSTRAINT FK_6B64D7FF4D0FDD48 FOREIGN KEY (custom_field_id) REFERENCES custom_fields (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lead_contacts ADD CONSTRAINT FK_9913977055458D FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lead_contacts ADD CONSTRAINT FK_99139770E7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_field_values DROP FOREIGN KEY FK_6B64D7FF4D0FDD48');
        $this->addSql('ALTER TABLE lead_contacts DROP FOREIGN KEY FK_9913977055458D');
        $this->addSql('ALTER TABLE lead_contacts DROP FOREIGN KEY FK_99139770E7A1254A');
        $this->addSql('DROP TABLE contacts');
        $this->addSql('DROP TABLE custom_fields');
        $this->addSql('DROP TABLE custom_field_values');
        $this->addSql('DROP TABLE leads');
        $this->addSql('DROP TABLE lead_contacts');
    }
}
