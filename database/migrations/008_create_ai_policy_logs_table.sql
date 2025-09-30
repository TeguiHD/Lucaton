-- Migración: Logs de políticas de IA
-- Fecha: 2025-09-24
-- Descripción: Registro de decisiones de moderación y políticas

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ai_policy_logs;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS ai_policy_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ai_generation_id BIGINT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    policy_type ENUM('content_filter','rate_limit','usage_quota','moderation','safety_check') NOT NULL,
    action ENUM('allowed','blocked','flagged','reviewed') NOT NULL,
    reason TEXT NULL,
    confidence_score DECIMAL(4,2) NULL,
    flagged_content TEXT NULL,
    reviewer_id INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_policy_logs_generation FOREIGN KEY (ai_generation_id) REFERENCES ai_generations(id) ON DELETE CASCADE,
    CONSTRAINT fk_policy_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_policy_logs_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_policy_logs_generation (ai_generation_id),
    INDEX idx_policy_logs_user (user_id),
    INDEX idx_policy_logs_type (policy_type),
    INDEX idx_policy_logs_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
