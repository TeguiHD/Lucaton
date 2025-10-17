-- Migración: Archivos adjuntos para apelaciones de campañas
-- Fecha: 2025-10-16

CREATE TABLE IF NOT EXISTS campaign_appeal_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appeal_id INT UNSIGNED NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(160) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appeal_files_appeal FOREIGN KEY (appeal_id) REFERENCES campaign_appeals(id) ON DELETE CASCADE,
    CONSTRAINT fk_appeal_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_appeal_files_appeal (appeal_id),
    INDEX idx_appeal_files_user (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
