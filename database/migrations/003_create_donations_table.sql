-- Migración: Rediseño de donaciones
-- Fecha: 2025-09-24
-- Descripción: Tabla de donaciones con metadatos y soporte para pagos reales

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS donations;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS donations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    supporter_id INT UNSIGNED NULL,
    supporter_name VARCHAR(255) NULL,
    supporter_email VARCHAR(255) NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'CLP',
    payment_method ENUM('credit_card','debit_card','bank_transfer','paypal','webpay','manual') NOT NULL DEFAULT 'credit_card',
    payment_provider VARCHAR(50) NULL,
    payment_reference VARCHAR(120) NULL,
    status ENUM('pending','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    is_anonymous BOOLEAN NOT NULL DEFAULT FALSE,
    message TEXT NULL,
    metadata JSON NULL,
    donor_ip VARCHAR(45) NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_donation_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_donation_supporter FOREIGN KEY (supporter_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_donations_campaign_status (campaign_id, status),
    INDEX idx_donations_supporter (supporter_id),
    INDEX idx_donations_status (status),
    INDEX idx_donations_created_at (created_at),
    INDEX idx_donations_payment_reference (payment_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
