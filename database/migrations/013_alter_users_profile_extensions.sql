-- Migración: Extender tabla users con campos de perfil y preferencias
-- Fecha: 2025-09-29
-- Descripción: Agrega soporte para solicitudes de cambio de nombre, preferencias de comunicación y metadatos de seguridad

ALTER TABLE users
    ADD COLUMN location VARCHAR(150) NULL AFTER phone,
    ADD COLUMN social_links TEXT NULL AFTER location,
    ADD COLUMN pref_product_updates TINYINT(1) NOT NULL DEFAULT 1 AFTER bio,
    ADD COLUMN pref_campaign_tips TINYINT(1) NOT NULL DEFAULT 1 AFTER pref_product_updates,
    ADD COLUMN pref_donation_alerts TINYINT(1) NOT NULL DEFAULT 1 AFTER pref_campaign_tips,
    ADD COLUMN password_updated_at TIMESTAMP NULL AFTER password_reset_expires_at,
    ADD COLUMN password_reset_at TIMESTAMP NULL AFTER password_updated_at,
    ADD COLUMN requested_first_name VARCHAR(100) NULL AFTER last_name,
    ADD COLUMN requested_last_name VARCHAR(100) NULL AFTER requested_first_name,
    ADD COLUMN name_review_status ENUM('pending','approved','rejected') NULL AFTER requested_last_name,
    ADD COLUMN name_review_notes VARCHAR(255) NULL AFTER name_review_status,
    ADD COLUMN name_reviewed_at TIMESTAMP NULL AFTER name_review_notes;
