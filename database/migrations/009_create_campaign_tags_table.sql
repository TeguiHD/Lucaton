-- Migración: Sistema de etiquetas para campañas
-- Fecha: 2025-09-24
-- Descripción: Catálogo de tags reutilizables y asignaciones a campañas

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS campaign_tag_map;
DROP TABLE IF EXISTS tags;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    tag_type ENUM('cause','audience','region','urgency','format','custom') NOT NULL DEFAULT 'custom',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tags_type (tag_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_tag_map (
    campaign_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (campaign_id, tag_id),
    CONSTRAINT fk_campaign_tag_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_tag_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_tag_user FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tags (name, slug, tag_type, description) VALUES
    ('Alta prioridad', 'alta-prioridad', 'urgency', 'Campañas que requieren apoyo inmediato'),
    ('Innovación social', 'innovacion-social', 'cause', 'Iniciativas con foco en innovación'),
    ('Municipalidades', 'municipalidades', 'audience', 'Campañas articuladas con gobiernos locales'),
    ('Rural', 'rural', 'region', 'Campañas situadas en zonas rurales'),
    ('Salud mental', 'salud-mental', 'cause', 'Apoyo psicológico y emocional')
ON DUPLICATE KEY UPDATE name = VALUES(name), tag_type = VALUES(tag_type), description = VALUES(description);
