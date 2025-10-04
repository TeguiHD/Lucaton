-- Migración: Agregar rol de superadministrador
-- Fecha: 2025-02-14

INSERT INTO roles (slug, name, is_admin)
VALUES ('superadmin', 'Super Administrador', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_admin = VALUES(is_admin);

-- Asegurar que los registros existentes con firma de rol se regeneren
UPDATE users u
SET role_signature = NULL
WHERE u.role_signature IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM roles r
      WHERE r.id = u.role_id
        AND r.slug IN ('admin', 'superadmin')
  );
