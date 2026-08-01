-- =====================================================================
-- F-17 Tracto: campos adicionales para alinear el formulario con el
-- formato oficial NOM-068.
--
-- Conceptos: inyectores de agua, válvula de pedal/liberación rápida,
-- pernos U, brazo de control, desglose combustible/escape y RIN por llanta.
--
-- Tipo de columna: enum('CUMPLE','NO CUMPLE','N/A') DEFAULT NULL
--
-- IDEMPOTENTE: agrega cada columna solo si no existe (seguro aunque ya
-- se haya ejecutado alter_inspeccion_campos_txt_nom.sql u otro script).
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

-- Iluminación: inyectores de agua del limpiaparabrisas (punto NOM 72).
CALL _add_col_cumple('inspeccion_iluminacion', 'inyectores_agua', 'limpiaparabrisas');

-- Suspensión delantera: pernos tipo "U" y brazo de control (punto NOM 13).
CALL _add_col_cumple('inspeccion_suspension', 'pernos_tipo_u', 'muelles');
CALL _add_col_cumple('inspeccion_suspension', 'brazo_control', 'pernos_tipo_u');

-- Sistema de aire: válvula de pedal y liberación rápida (punto NOM 39).
CALL _add_col_cumple('inspeccion_sistema_aire', 'valvula_pedal',             'valvulas_sistema');
CALL _add_col_cumple('inspeccion_sistema_aire', 'valvula_liberacion_rapida', 'valvula_pedal');

-- Chasis: desglose combustible (NOM 2) y escape (NOM 4).
CALL _add_col_cumple('inspeccion_chasis', 'combustible_tapon',          'sistema_combustible');
CALL _add_col_cumple('inspeccion_chasis', 'combustible_tanque',         'combustible_tapon');
CALL _add_col_cumple('inspeccion_chasis', 'combustible_cubierta_jaula', 'combustible_tanque');
CALL _add_col_cumple('inspeccion_chasis', 'combustible_lineas_bomba',   'combustible_cubierta_jaula');
CALL _add_col_cumple('inspeccion_chasis', 'escape_multiple',            'sistema_escape');
CALL _add_col_cumple('inspeccion_chasis', 'escape_mofle',               'escape_multiple');
CALL _add_col_cumple('inspeccion_chasis', 'escape_tubos',               'escape_mofle');
CALL _add_col_cumple('inspeccion_chasis', 'escape_montaje',             'escape_tubos');

-- Llantas: RIN sujetadores y artillería (punto NOM 78).
CALL _add_col_cumple('inspeccion_llantas', 'rin_sujetadores', 'rin_condicion');
CALL _add_col_cumple('inspeccion_llantas', 'rin_artilleria',  'rin_sujetadores');

DROP PROCEDURE IF EXISTS _add_col_cumple;
