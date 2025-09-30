-- Migración: Tabla de apelaciones de campañas (rediseño)
-- Fecha: 2025-09-24
-- Descripción: Estructura consistente con nuevo flujo de revisión

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS campaign_appeals;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS campaign_appeals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    additional_evidence TEXT NULL,
    status ENUM('pending','under_review','approved','rejected','closed') NOT NULL DEFAULT 'pending',
    admin_response TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_appeal_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_appeal_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_appeal_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaign_appeals_campaign (campaign_id),
    INDEX idx_campaign_appeals_user (user_id),
    INDEX idx_campaign_appeals_status (status),
    INDEX idx_campaign_appeals_reviewed (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
