CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(64) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE roles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO roles (slug, name, is_admin)
VALUES
    ('user', 'Usuario', 0),
    ('admin', 'Administrador', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_admin = VALUES(is_admin);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role_id INT UNSIGNED NULL AFTER id;

UPDATE users SET role_id = (
    SELECT id FROM roles
    WHERE slug COLLATE utf8mb4_unicode_ci = users.role COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

UPDATE users
SET role_id = (SELECT id FROM roles WHERE slug = 'user' LIMIT 1)
WHERE role_id IS NULL;

ALTER TABLE users
    DROP FOREIGN KEY IF EXISTS fk_users_role_id;

DROP INDEX IF EXISTS idx_users_role_id ON users;

ALTER TABLE users
    MODIFY role_id INT UNSIGNED NOT NULL,
    ADD INDEX idx_users_role_id (role_id),
    ADD CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role_signature VARCHAR(128) NULL AFTER role_id;

ALTER TABLE users
    DROP COLUMN IF EXISTS role;
