-- Migración: Materiales y evidencias de campaña
-- Fecha: 2025-09-24
-- Descripción: Tabla para activos multimedia asociados a campañas

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS campaign_media;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS campaign_media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    media_type ENUM('image','video','document','audio','link') NOT NULL DEFAULT 'image',
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NULL,
    file_size INT UNSIGNED NULL,
    title VARCHAR(255) NULL,
    caption VARCHAR(255) NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_media_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_media_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaign_media_primary (campaign_id, is_primary),
    INDEX idx_campaign_media_sort (campaign_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
