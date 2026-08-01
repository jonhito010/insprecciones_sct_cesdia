-- =====================================================================
-- Alineación de los formularios con los conceptos NOM de los TXT oficiales
-- (webroot/camposTXT). Agrega los campos faltantes detectados en la
-- validación: dirección, suspensión delantera, iluminación, frenos
-- hidráulicos (F-18) y válvulas del sistema de aire.
--
-- TODAS las columnas usan el mismo dominio que el resto de conceptos:
--   enum('CUMPLE','NO CUMPLE','N/A') DEFAULT NULL
--
-- El script es IDEMPOTENTE: usa un procedimiento que agrega la columna
-- solo si no existe, de modo que pueda ejecutarse aunque algunas columnas
-- ya hubieran sido creadas por scripts previos
-- (p. ej. alter_inspeccion_f17_campos_adicionales.sql).
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

-- Cabina / Sistema de dirección (puntos NOM 45-46).
CALL _add_col_cumple('inspeccion_cabina', 'operacion_direccion', 'volante');
CALL _add_col_cumple('inspeccion_cabina', 'juego_volante',       'operacion_direccion');
CALL _add_col_cumple('inspeccion_cabina', 'terminales_direccion', 'barra_acoplamiento');
CALL _add_col_cumple('inspeccion_cabina', 'junta_transversal',    'terminales_direccion');

-- Suspensión delantera: pernos tipo "U", brazo de control y amortiguadores (puntos NOM 13 y 21).
CALL _add_col_cumple('inspeccion_suspension', 'pernos_tipo_u', 'muelles');
CALL _add_col_cumple('inspeccion_suspension', 'brazo_control', 'pernos_tipo_u');
CALL _add_col_cumple('inspeccion_suspension', 'amortiguadores_delantera', 'brazo_control');
CALL _add_col_cumple('inspeccion_suspension', 'amortiguadores_trasera_2', 'salpicaderas');

-- Sistema de aire: válvula de pedal y de liberación rápida (punto NOM 39).
CALL _add_col_cumple('inspeccion_sistema_aire', 'valvula_pedal',             'valvulas_sistema');
CALL _add_col_cumple('inspeccion_sistema_aire', 'valvula_liberacion_rapida', 'valvula_pedal');

-- Frenos hidráulicos — F-18 Camión (puntos NOM 22, 26, 27, 28).
CALL _add_col_cumple('inspeccion_frenos', 'hid_recorrido',            'hid_pedal');
CALL _add_col_cumple('inspeccion_frenos', 'hid_indicador_advertencia', 'hid_recorrido');
CALL _add_col_cumple('inspeccion_frenos', 'hid_luz_indicadora',        'hid_indicador_advertencia');
CALL _add_col_cumple('inspeccion_frenos', 'hid_cables_acoplamiento',   'hid_luz_indicadora');
CALL _add_col_cumple('inspeccion_frenos', 'hid_libera_hidraulico',     'hid_cables_acoplamiento');
CALL _add_col_cumple('inspeccion_frenos', 'hid_abrazaderas',           'hid_valvulas_unidirec');
CALL _add_col_cumple('inspeccion_frenos', 'hid_booster',              'hid_abrazaderas');

DROP PROCEDURE IF EXISTS _add_col_cumple;
