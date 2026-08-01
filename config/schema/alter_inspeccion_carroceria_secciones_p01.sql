-- P0.1 · Separar columnas de carrocería F-19 por sección (sin borrar legacy).
-- Rollback: config/schema/rollback/alter_inspeccion_carroceria_secciones_p01.sql

DELIMITER //

DROP PROCEDURE IF EXISTS _add_col_cumple_p01 //
CREATE PROCEDURE _add_col_cumple_p01(
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

CALL _add_col_cumple_p01('inspeccion_carroceria', 'grano_lados_soporte', 'laterales_soporte');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'grano_piso', 'grano_lados_soporte');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'grano_carroceria_remaches', 'grano_piso');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'plataforma_plana', 'plataforma');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'plataforma_laterales_estacas', 'plataforma_plana');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'grava_laterales_soporte', 'laterales');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'grava_piso', 'grava_laterales_soporte');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'grava_puertas_tolva', 'grava_piso');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'sujecion_puntos_equipo', 'puntos_sujecion');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'sujecion_condicion_carga', 'sujecion_puntos_equipo');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'otro_piso', 'puertas');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'otro_puertas', 'otro_piso');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'otro_laterales', 'otro_puertas');
CALL _add_col_cumple_p01('inspeccion_carroceria', 'otro_sujetadores_mangueras', 'sujetadores_mangueras');

-- Migración conservadora: copiar legacy → nuevas si NULL.
UPDATE inspeccion_carroceria SET
  grano_lados_soporte = COALESCE(grano_lados_soporte, laterales_soporte),
  grano_piso = COALESCE(grano_piso, piso),
  grano_carroceria_remaches = COALESCE(grano_carroceria_remaches, carroceria_remaches),
  plataforma_plana = COALESCE(plataforma_plana, plataforma),
  plataforma_laterales_estacas = COALESCE(plataforma_laterales_estacas, laterales_estaca),
  grava_laterales_soporte = COALESCE(grava_laterales_soporte, laterales),
  grava_piso = COALESCE(grava_piso, CASE WHEN tipo_carroceria IN ('Tolva/grava') THEN piso ELSE NULL END),
  grava_puertas_tolva = COALESCE(grava_puertas_tolva, puertas_tolva),
  sujecion_puntos_equipo = COALESCE(sujecion_puntos_equipo, puntos_sujecion),
  sujecion_condicion_carga = COALESCE(sujecion_condicion_carga, condicion_carga),
  otro_piso = COALESCE(otro_piso, CASE WHEN tipo_carroceria IN ('Otra', 'Caja seca') THEN piso ELSE NULL END),
  otro_puertas = COALESCE(otro_puertas, puertas),
  otro_laterales = COALESCE(otro_laterales, CASE WHEN tipo_carroceria IN ('Otra', 'Caja seca') THEN laterales ELSE NULL END),
  otro_sujetadores_mangueras = COALESCE(otro_sujetadores_mangueras, sujetadores_mangueras);

DROP PROCEDURE IF EXISTS _add_col_cumple_p01;
