-- Migración: Generaciones de IA
-- Fecha: 2025-09-24
-- Descripción: Tabla para almacenar resultados y contexto de IA

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ai_generations;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS ai_generations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    context_entity_type ENUM('campaign','news_article','donation','internal','standalone') NOT NULL DEFAULT 'standalone',
    context_entity_id BIGINT UNSIGNED NULL,
    mode ENUM('text','image','both') NOT NULL DEFAULT 'text',
    prompt TEXT NOT NULL,
    input_parameters JSON NULL,
    model_used VARCHAR(120) NOT NULL,
    provider ENUM('openai','gemini','stability','anthropic') NOT NULL DEFAULT 'openai',
    output_text LONGTEXT NULL,
    output_asset_path VARCHAR(500) NULL,
    tokens_input INT UNSIGNED NULL,
    tokens_output INT UNSIGNED NULL,
    cost_estimate DECIMAL(10,5) NULL,
    latency_ms INT UNSIGNED NULL,
    status ENUM('pending','completed','failed','moderated','rejected') NOT NULL DEFAULT 'pending',
    moderation_result JSON NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_generations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ai_generations_user (user_id),
    INDEX idx_ai_generations_context (context_entity_type, context_entity_id),
    INDEX idx_ai_generations_status (status),
    INDEX idx_ai_generations_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
