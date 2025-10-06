-- Migración: Crear tabla de actualizaciones de campañas
-- Fecha: 2025-02-14

CREATE TABLE IF NOT EXISTS campaign_updates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NULL,
    body TEXT NOT NULL,
    media LONGTEXT NULL,
    status ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'published',
    visibility ENUM('public','supporters','private') NOT NULL DEFAULT 'public',
    heart_count INT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_updates_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_updates_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_campaign_updates_campaign (campaign_id, status, published_at),
    INDEX idx_campaign_updates_visibility (visibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
