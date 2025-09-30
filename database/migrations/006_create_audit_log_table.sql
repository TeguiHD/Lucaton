-- Migración: Registro centralizado de auditoría
-- Fecha: 2025-09-24
-- Descripción: Tabla para eventos de auditoría y trazabilidad

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_events;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS audit_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(60) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(120) NOT NULL,
    user_id INT UNSIGNED NULL,
    context JSON NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
