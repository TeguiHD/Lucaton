-- Migración: Rediseño de campañas y taxonomías
-- Fecha: 2025-09-24
-- Descripción: Estructura modular para campañas, categorías, detalles y métricas

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS campaign_status_history;
DROP TABLE IF EXISTS campaign_metrics;
DROP TABLE IF EXISTS campaign_details;
DROP TABLE IF EXISTS campaign_categories;
DROP TABLE IF EXISTS campaigns;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS campaign_categories (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    color_hex CHAR(7) NULL,
    icon VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campaign_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    summary VARCHAR(400) NOT NULL,
    story MEDIUMTEXT NOT NULL,
    goal_amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'CLP',
    status ENUM('draft','under_review','published','paused','completed','cancelled','archived') NOT NULL DEFAULT 'draft',
    visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public',
    start_date DATE NULL,
    end_date DATE NULL,
    published_at DATETIME NULL,
    approved_at DATETIME NULL,
    approved_by INT UNSIGNED NULL,
    cover_image_url VARCHAR(500) NULL,
    video_url VARCHAR(500) NULL,
    ai_assisted BOOLEAN NOT NULL DEFAULT FALSE,
    featured BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_category FOREIGN KEY (category_id) REFERENCES campaign_categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_campaign_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaign_owner (owner_id),
    INDEX idx_campaign_category (category_id),
    INDEX idx_campaign_status (status),
    INDEX idx_campaign_visibility (visibility),
    INDEX idx_campaign_dates (end_date, start_date),
    INDEX idx_campaign_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_details (
    campaign_id INT UNSIGNED PRIMARY KEY,
    beneficiary_type ENUM('individual','organization','community') NOT NULL DEFAULT 'individual',
    beneficiary_name VARCHAR(255) NOT NULL,
    beneficiary_contact VARCHAR(255) NULL,
    location_label VARCHAR(255) NULL,
    impact_summary VARCHAR(255) NULL,
    transparency_plan TEXT NULL,
    support_channels JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_details_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_metrics (
    campaign_id INT UNSIGNED PRIMARY KEY,
    raised_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    donor_count INT UNSIGNED NOT NULL DEFAULT 0,
    follower_count INT UNSIGNED NOT NULL DEFAULT 0,
    share_count INT UNSIGNED NOT NULL DEFAULT 0,
    view_count INT UNSIGNED NOT NULL DEFAULT 0,
    average_donation DECIMAL(12,2) NOT NULL DEFAULT 0,
    last_donation_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_metrics_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    previous_status ENUM('draft','under_review','published','paused','completed','cancelled','archived') NULL,
    new_status ENUM('draft','under_review','published','paused','completed','cancelled','archived') NOT NULL,
    changed_by INT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_history_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status_history_campaign (campaign_id),
    INDEX idx_status_history_status (new_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO campaign_categories (name, slug, description, color_hex, icon) VALUES
    ('Educación', 'education', 'Apoya iniciativas educativas y de formación', '#1D4ED8', 'academic-cap'),
    ('Salud y bienestar', 'health', 'Campañas orientadas a tratamientos y cuidados', '#DC2626', 'heart'),
    ('Medio ambiente', 'environment', 'Protege la naturaleza y la sostenibilidad', '#059669', 'leaf'),
    ('Comunidad y barrio', 'community', 'Proyectos colectivos que cambian barrios', '#7C3AED', 'users'),
    ('Emprendimiento social', 'entrepreneurship', 'Ideas que generan impacto social sostenible', '#DB2777', 'light-bulb'),
    ('Emergencias', 'emergency', 'Responde a situaciones críticas y urgentes', '#F59E0B', 'exclamation-triangle'),
    ('Arte y cultura', 'arts', 'Promueve expresiones artísticas y culturales', '#EC4899', 'music-note'),
    ('Tecnología solidaria', 'technology', 'Innovación al servicio de causas sociales', '#0EA5E9', 'cpu'),
    ('Deporte y recreación', 'sports', 'Impulsa el deporte y las actividades saludables', '#22C55E', 'flag'),
    ('Protección animal', 'animals', 'Cuida y protege a los animales', '#F97316', 'paw'),
    ('Otras causas', 'other', 'Iniciativas que no encajan en otra categoría', '#6B7280', 'sparkles')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), color_hex = VALUES(color_hex), icon = VALUES(icon);
