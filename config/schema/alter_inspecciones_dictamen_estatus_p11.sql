-- P1.1 · Separar dictamen (CUMPLE/NO CUMPLE) del estatus del registro.
-- Rollback: config/schema/rollback/alter_inspecciones_dictamen_estatus_p11.sql

DELIMITER //
DROP PROCEDURE IF EXISTS _add_col_if_missing_p11 //
CREATE PROCEDURE _add_col_if_missing_p11(
    IN p_tabla VARCHAR(64),
    IN p_columna VARCHAR(64),
    IN p_ddl TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_tabla AND COLUMN_NAME = p_columna
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_tabla, '` ADD COLUMN ', p_ddl);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL _add_col_if_missing_p11('inspecciones', 'dictamen',
  '`dictamen` enum(''CUMPLE'',''NO CUMPLE'') DEFAULT NULL AFTER `resultado`');
CALL _add_col_if_missing_p11('inspecciones', 'estatus_registro',
  '`estatus_registro` enum(''ACTIVA'',''CANCELADA'') NOT NULL DEFAULT ''ACTIVA'' AFTER `dictamen`');

-- Migrar desde resultado legacy
UPDATE inspecciones SET
  dictamen = CASE
    WHEN resultado = 'APROBADO' THEN 'CUMPLE'
    WHEN resultado = 'RECHAZADO' THEN 'NO CUMPLE'
    ELSE dictamen
  END,
  estatus_registro = CASE
    WHEN resultado = 'CANCELADO' THEN 'CANCELADA'
    ELSE COALESCE(estatus_registro, 'ACTIVA')
  END
WHERE dictamen IS NULL OR resultado = 'CANCELADO';

DROP PROCEDURE IF EXISTS _add_col_if_missing_p11;
