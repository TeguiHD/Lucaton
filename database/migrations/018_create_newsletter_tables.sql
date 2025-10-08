-- Crear tabla de suscripciones al newsletter
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(120) NULL,
    status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
    unsubscribe_token VARCHAR(64) NOT NULL UNIQUE,
    preferences JSON NULL,
    last_sent_at DATETIME NULL,
    unsubscribed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para campañas/envíos de newsletter
CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(180) NOT NULL,
    template_key VARCHAR(64) NOT NULL,
    message TEXT NOT NULL,
    cta_label VARCHAR(80) NULL,
    cta_url VARCHAR(255) NULL,
    preview_path VARCHAR(255) NULL,
    recipient_count INT UNSIGNED DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para registrar destinatarios por campaña
CREATE TABLE IF NOT EXISTS newsletter_campaign_recipients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    subscription_id INT UNSIGNED NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    preview_path VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    error_message VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES newsletter_subscriptions(id) ON DELETE SET NULL,
    INDEX idx_campaign_status (campaign_id, status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para búsquedas rápidas de token
CREATE INDEX idx_newsletter_token ON newsletter_subscriptions (unsubscribe_token);
