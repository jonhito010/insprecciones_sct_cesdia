-- P1.3 · Volante / Holgura en cm (F-17/F-18/F-21).
-- Rollback: config/schema/rollback/alter_inspecciones_volante_holgura_p13.sql

DELIMITER //
DROP PROCEDURE IF EXISTS _add_col_dec_p13 //
CREATE PROCEDURE _add_col_dec_p13(
    IN p_columna VARCHAR(64),
    IN p_after VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inspecciones' AND COLUMN_NAME = p_columna
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `inspecciones` ADD COLUMN `', p_columna,
            '` decimal(5,2) DEFAULT NULL AFTER `', p_after, '`'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL _add_col_dec_p13('volante_cm', 'odometro');
CALL _add_col_dec_p13('holgura_cm', 'volante_cm');

DROP PROCEDURE IF EXISTS _add_col_dec_p13;
