-- Migración: Tabla de embeddings semánticos
-- Fecha: 2025-09-24
-- Descripción: Embeddings para búsqueda y personalización

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS embeddings;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS embeddings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('campaign','user','news_article','ai_generation') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    content_hash VARCHAR(64) NOT NULL,
    embedding_vector JSON NOT NULL,
    model_used VARCHAR(120) NOT NULL DEFAULT 'text-embedding-3-large',
    dimensions INT UNSIGNED NOT NULL DEFAULT 3072,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_entity_hash (entity_type, entity_id, content_hash),
    INDEX idx_embeddings_entity (entity_type, entity_id),
    INDEX idx_embeddings_model (model_used),
    INDEX idx_embeddings_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
