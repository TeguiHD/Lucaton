## Modelo de Datos (resumen)

Entidades principales
- usuarios: id, nombre_completo, email (único), password_hash, rol ('user'|'admin'), redes(JSON), fecha_registro
- campanas: id, id_usuario, titulo, descripcion, meta_monetaria, fecha_inicio, fecha_fin, estado, imagen_url, evidencia_requerida, created_at, updated_at
- evidencias: id, id_campana, file_path, mime_type, uploaded_at
- apelaciones: id, id_campana, mensaje, fecha_creacion, estado('pendiente'|'resuelta')
- donaciones (simuladas): id, id_campana, id_usuario, monto, fecha
- auditoria_estados: id, id_campana, estado_anterior, estado_nuevo, id_admin, comentario, fecha

## SQL de Referencia (BD + tablas + índices)

Crear BD y usuario con privilegios mínimos:
```sql
CREATE DATABASE IF NOT EXISTS lucaton CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'lucaton_app'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON lucaton.* TO 'lucaton_app'@'localhost';
FLUSH PRIVILEGES;
```

Tablas (InnoDB utf8mb4):
```sql
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('user','admin') DEFAULT 'user',
  redes JSON NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campanas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,
  meta_monetaria DECIMAL(12,2) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  estado ENUM('pendiente','aprobada','rechazada','pausada','finalizada') DEFAULT 'pendiente',
  imagen_url VARCHAR(255) NULL,
  evidencia_requerida TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evidencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_campana INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_campana) REFERENCES campanas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS apelaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_campana INT NOT NULL,
  mensaje TEXT NOT NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente','resuelta') DEFAULT 'pendiente',
  FOREIGN KEY (id_campana) REFERENCES campanas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_campana INT NOT NULL,
  id_usuario INT NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_campana) REFERENCES campanas(id),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria_estados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_campana INT NOT NULL,
  estado_anterior VARCHAR(20),
  estado_nuevo VARCHAR(20) NOT NULL,
  id_admin INT NOT NULL,
  comentario TEXT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_campana) REFERENCES campanas(id),
  FOREIGN KEY (id_admin) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices útiles
CREATE INDEX idx_campanas_estado ON campanas(estado);
CREATE INDEX idx_campanas_fecha_fin ON campanas(fecha_fin);
CREATE INDEX idx_donaciones_campana_fecha ON donaciones(id_campana, fecha);
-- IA: registro de generaciones y políticas
CREATE TABLE IF NOT EXISTS ai_generations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('text','image','moderation','alt_text','tagging','embedding') NOT NULL,
  input_text TEXT NULL,
  prompt_used TEXT NULL,
  output_path VARCHAR(255) NULL,
  output_mime VARCHAR(100) NULL,
  status ENUM('pending','denied','generated','published') DEFAULT 'pending',
  policy_flags JSON NULL,
  used_in_campaign_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES usuarios(id),
  FOREIGN KEY (used_in_campaign_id) REFERENCES campanas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_policy_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  generation_id INT NOT NULL,
  violated_policies JSON NOT NULL,
  message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (generation_id) REFERENCES ai_generations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campos opcionales en campañas para IA
ALTER TABLE campanas
  ADD COLUMN alt_text VARCHAR(255) NULL,
  ADD COLUMN tags JSON NULL,
  ADD COLUMN ai_flags JSON NULL,
  ADD COLUMN embedding JSON NULL;
```
