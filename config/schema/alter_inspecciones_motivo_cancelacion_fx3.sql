-- INC-3 · Motivo de cancelación (soft-delete).
-- ⚠️ Dejar listo; aplicar tras respaldo (ver FIX_HISTORIAL_INMEDIATOS.md).
-- Rollback: config/schema/rollback/alter_inspecciones_motivo_cancelacion_fx3.sql

DELIMITER //
DROP PROCEDURE IF EXISTS _add_col_if_missing_fx3 //
CREATE PROCEDURE _add_col_if_missing_fx3(
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

CALL _add_col_if_missing_fx3(
  'inspecciones',
  'motivo_cancelacion',
  '`motivo_cancelacion` VARCHAR(255) NULL DEFAULT NULL AFTER `estatus_registro`'
);

DROP PROCEDURE IF EXISTS _add_col_if_missing_fx3;
