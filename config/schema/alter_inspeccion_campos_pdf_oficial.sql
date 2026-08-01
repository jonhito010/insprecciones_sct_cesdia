-- =====================================================================
-- Alineación final con los PDFs oficiales (webroot/camposTXT/*.pdf).
-- Agrega los conceptos que aún no tenían columna tras comparar los 5
-- formatos (F-17 … F-21). Todas las columnas usan el mismo dominio:
--   enum('CUMPLE','NO CUMPLE','N/A') DEFAULT NULL
--
-- Script IDEMPOTENTE: agrega cada columna solo si no existe.
-- =====================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS _add_col_cumple //
CREATE PROCEDURE _add_col_cumple(
    IN p_tabla   VARCHAR(64),
    IN p_columna VARCHAR(64),
    IN p_after   VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_tabla
          AND COLUMN_NAME  = p_columna
    ) THEN
        SET @ddl = CONCAT(
            'ALTER TABLE `', p_tabla, '` ',
            'ADD COLUMN `', p_columna, '` ',
            'enum(''CUMPLE'',''NO CUMPLE'',''N/A'') DEFAULT NULL ',
            'AFTER `', p_after, '`'
        );
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- Iluminación / Luces (puntos NOM 53, 65).
CALL _add_col_cumple('inspeccion_iluminacion', 'faros_montaje',   'faros_altura');
CALL _add_col_cumple('inspeccion_iluminacion', 'luces_peligro',   'direccionales');
CALL _add_col_cumple('inspeccion_iluminacion', 'parabrisas_tipo', 'parabrisas');

-- Sistema de combustible Gas LP — F-18 (punto NOM 3).
CALL _add_col_cumple('inspeccion_chasis', 'gaslp_soporte_tanque',   'combustible_gas_lp');
CALL _add_col_cumple('inspeccion_chasis', 'gaslp_etiqueta_cilindro', 'gaslp_soporte_tanque');
CALL _add_col_cumple('inspeccion_chasis', 'gaslp_condicion',         'gaslp_etiqueta_cilindro');
CALL _add_col_cumple('inspeccion_chasis', 'gaslp_cinchos',           'gaslp_condicion');

-- Frenos — F-18 (puntos NOM 22, 31).
CALL _add_col_cumple('inspeccion_frenos', 'estac_balata',         'hid_libera_hidraulico');
CALL _add_col_cumple('inspeccion_frenos', 'hid_liquido_condicion', 'hid_cilindros');

-- Carrocería — F-19 otro tipo (punto NOM 60).
CALL _add_col_cumple('inspeccion_carroceria', 'sujetadores_mangueras', 'condicion_carga');

DROP PROCEDURE IF EXISTS _add_col_cumple;
