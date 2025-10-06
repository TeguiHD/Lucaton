-- Migración: Agregar columnas de financiamiento a campaigns
-- Fecha: 2025-02-14

SET @schema_name := DATABASE();

-- funded_at
SET @needs_funded_at := (
    SELECT COUNT(*) = 0
    FROM information_schema.columns
    WHERE table_schema = @schema_name
      AND table_name = 'campaigns'
      AND column_name = 'funded_at'
);
SET @sql_funded_at := IF(
    @needs_funded_at,
    'ALTER TABLE campaigns ADD COLUMN funded_at DATETIME NULL AFTER published_at',
    'SELECT 1'
);
PREPARE stmt FROM @sql_funded_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- funding_notified_at
SET @needs_funding_notified := (
    SELECT COUNT(*) = 0
    FROM information_schema.columns
    WHERE table_schema = @schema_name
      AND table_name = 'campaigns'
      AND column_name = 'funding_notified_at'
);
SET @sql_funding_notified := IF(
    @needs_funding_notified,
    'ALTER TABLE campaigns ADD COLUMN funding_notified_at DATETIME NULL AFTER funded_at',
    'SELECT 1'
);
PREPARE stmt FROM @sql_funding_notified;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- funding_celebrated_at
SET @needs_funding_celebrated := (
    SELECT COUNT(*) = 0
    FROM information_schema.columns
    WHERE table_schema = @schema_name
      AND table_name = 'campaigns'
      AND column_name = 'funding_celebrated_at'
);
SET @sql_funding_celebrated := IF(
    @needs_funding_celebrated,
    'ALTER TABLE campaigns ADD COLUMN funding_celebrated_at DATETIME NULL AFTER funding_notified_at',
    'SELECT 1'
);
PREPARE stmt FROM @sql_funding_celebrated;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para funded_at
SET @has_idx_funded := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @schema_name
      AND table_name = 'campaigns'
      AND index_name = 'idx_campaigns_funded_at'
);
SET @sql_idx_funded := IF(
    @has_idx_funded = 0,
    'CREATE INDEX idx_campaigns_funded_at ON campaigns (funded_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql_idx_funded;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
