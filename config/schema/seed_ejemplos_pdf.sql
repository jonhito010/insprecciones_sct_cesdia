-- =====================================================================
-- Datos de ejemplo: una inspección de cada tipo de formulario
--   F-17 Tracto (T3) · F-18 Camión (C3) · F-19 Remolque (S3)
--   F-20 Dolly (D1)  · F-21 Autobús (AB)
--
-- 1) Borra las inspecciones/ejemplos actuales (y sus subtablas).
-- 2) Crea un vehículo por tipo (marca 'EJEMPLO').
-- 3) Crea una inspección por tipo con subtablas en CUMPLE y llantas
--    según el esquema real del tipo (mismo que el formulario/PDF).
-- =====================================================================

SET SESSION group_concat_max_len = 1000000;

-- ── 1. Limpieza ──────────────────────────────────────────────────────
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM inspeccion_acoplamiento;
DELETE FROM inspeccion_cabina;
DELETE FROM inspeccion_carroceria;
DELETE FROM inspeccion_chasis;
DELETE FROM inspeccion_frenos;
DELETE FROM inspeccion_iluminacion;
DELETE FROM inspeccion_llantas;
DELETE FROM inspeccion_rines;
DELETE FROM inspeccion_sistema_aire;
DELETE FROM inspeccion_suspension;
DELETE FROM inspecciones;
DELETE FROM vehiculos WHERE marca = 'EJEMPLO';
SET FOREIGN_KEY_CHECKS = 1;

-- ── Procedimiento: inserta una subtabla con todos sus enum en 'CUMPLE' ─
DELIMITER //
DROP PROCEDURE IF EXISTS _seed_child //
CREATE PROCEDURE _seed_child(IN p_tabla VARCHAR(64), IN p_insp INT)
BEGIN
    DECLARE v_cols TEXT;

    SET @ins = CONCAT('INSERT INTO `', p_tabla, '` (inspeccion_id) VALUES (', p_insp, ')');
    PREPARE s FROM @ins; EXECUTE s; DEALLOCATE PREPARE s;

    SELECT GROUP_CONCAT(CONCAT('`', COLUMN_NAME, '`=''CUMPLE''') SEPARATOR ', ')
      INTO v_cols
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = p_tabla
       AND DATA_TYPE    = 'enum'
       AND COLUMN_NAME NOT IN ('posicion');

    IF v_cols IS NOT NULL THEN
        SET @upd = CONCAT('UPDATE `', p_tabla, '` SET ', v_cols, ' WHERE inspeccion_id = ', p_insp);
        PREPARE s2 FROM @upd; EXECUTE s2; DEALLOCATE PREPARE s2;
    END IF;
END //
DELIMITER ;

SET @prop := (SELECT id FROM propietarios ORDER BY id LIMIT 1);
SET @uni  := (SELECT id FROM unidades_inspeccion ORDER BY id LIMIT 1);
SET @tec  := (SELECT id FROM tecnicos ORDER BY id LIMIT 1);

-- =====================================================================
-- F-17 TRACTO (T3, 10 llantas)
-- =====================================================================
INSERT INTO vehiculos (propietario_id, niv, placas, marca, ejes, anio, tipo_vehiculo, modalidad, tipo_servicio, folio_tc)
VALUES (@prop, '3T3EJEMPLO0000001', 'EJM-F17', 'EJEMPLO', 3, 2022, 'T3', 'AUTOTRANSPORTE FEDERAL', 'CARGA GENERAL', 'TC-F17');
SET @veh := LAST_INSERT_ID();
INSERT INTO inspecciones (unidad_inspeccion_id, vehiculo_id, tipo_formulario, tecnico_id, folio_dictamen, odometro, fecha_inspeccion, vehiculo_presentado, resultado, puntuacion, tipo_camara_frenado, camara_abrazadera_mm, varilla_ll1_2_mm, varilla_ll1_2_resultado, varilla_ll3_4_mm, varilla_ll3_4_resultado, varilla_ll5_6_mm, varilla_ll5_6_resultado, varilla_ll7_8_mm, varilla_ll7_8_resultado, observaciones)
VALUES (@uni, @veh, 'F17_TRACTO', @tec, 'M000017', 154200, CURDATE(), 'VACIO', 'APROBADO', 100.00, 'CAMARA DE FRENO TIPO ABRAZADERA', 2.50, 2.00, 'CUMPLE', 2.10, 'CUMPLE', 2.00, 'CUMPLE', 1.90, 'CUMPLE', 'Inspección de ejemplo F-17 Tracto.');
SET @insp := LAST_INSERT_ID();
CALL _seed_child('inspeccion_iluminacion', @insp);
CALL _seed_child('inspeccion_suspension',  @insp);
CALL _seed_child('inspeccion_chasis',      @insp);
CALL _seed_child('inspeccion_frenos',      @insp);
CALL _seed_child('inspeccion_sistema_aire',@insp);
CALL _seed_child('inspeccion_cabina',      @insp);
CALL _seed_child('inspeccion_acoplamiento',@insp);
UPDATE inspeccion_sistema_aire SET caida_presion_psi=1.50, tiempo_carga_min=2.00, presion_cierre_con_disp=40.00, presion_cierre_sin_disp=20.00 WHERE inspeccion_id=@insp;
INSERT INTO inspeccion_llantas (inspeccion_id, numero_llanta, posicion, profundidad_mm, profundidad_cumple, presion_psi, presion_cumple, banda_rodamiento, costados, rin_condicion, rin_sujetadores, rin_artilleria) VALUES
(@insp,1,'EXTERNA', 8.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,1,'INTERNA', 8.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,5,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,5,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE');

-- =====================================================================
-- F-18 CAMIÓN (C3, 10 llantas)
-- =====================================================================
INSERT INTO vehiculos (propietario_id, niv, placas, marca, ejes, anio, tipo_vehiculo, modalidad, tipo_servicio, folio_tc)
VALUES (@prop, '3C3EJEMPLO0000001', 'EJM-F18', 'EJEMPLO', 3, 2021, 'C3', 'AUTOTRANSPORTE FEDERAL', 'CARGA GENERAL', 'TC-F18');
SET @veh := LAST_INSERT_ID();
INSERT INTO inspecciones (unidad_inspeccion_id, vehiculo_id, tipo_formulario, tecnico_id, folio_dictamen, odometro, fecha_inspeccion, vehiculo_presentado, resultado, puntuacion, tipo_camara_frenado, camara_abrazadera_mm, varilla_ll1_2_mm, varilla_ll1_2_resultado, varilla_ll3_4_mm, varilla_ll3_4_resultado, varilla_ll5_6_mm, varilla_ll5_6_resultado, observaciones)
VALUES (@uni, @veh, 'F18_CAMION', @tec, 'M000018', 98750, CURDATE(), 'VACIO', 'APROBADO', 100.00, 'CAMARA DE FRENO TIPO ABRAZADERA', 2.50, 2.00, 'CUMPLE', 2.10, 'CUMPLE', 2.00, 'CUMPLE', 'Inspección de ejemplo F-18 Camión.');
SET @insp := LAST_INSERT_ID();
CALL _seed_child('inspeccion_iluminacion', @insp);
CALL _seed_child('inspeccion_suspension',  @insp);
CALL _seed_child('inspeccion_chasis',      @insp);
CALL _seed_child('inspeccion_frenos',      @insp);
CALL _seed_child('inspeccion_sistema_aire',@insp);
CALL _seed_child('inspeccion_cabina',      @insp);
UPDATE inspeccion_sistema_aire SET caida_presion_psi=1.50, tiempo_carga_min=2.00 WHERE inspeccion_id=@insp;
INSERT INTO inspeccion_llantas (inspeccion_id, numero_llanta, posicion, profundidad_mm, profundidad_cumple, presion_psi, presion_cumple, banda_rodamiento, costados, rin_condicion, rin_sujetadores, rin_artilleria) VALUES
(@insp,1,'EXTERNA', 8.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,1,'INTERNA', 8.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,5,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,5,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE');

-- =====================================================================
-- F-19 REMOLQUE (S3, 12 llantas)
-- =====================================================================
INSERT INTO vehiculos (propietario_id, niv, placas, marca, ejes, anio, tipo_vehiculo, modalidad, tipo_servicio, folio_tc)
VALUES (@prop, '3S3EJEMPLO0000001', 'EJM-F19', 'EJEMPLO', 3, 2020, 'S3', 'AUTOTRANSPORTE FEDERAL', 'CARGA GENERAL', 'TC-F19');
SET @veh := LAST_INSERT_ID();
INSERT INTO inspecciones (unidad_inspeccion_id, vehiculo_id, tipo_formulario, tecnico_id, folio_dictamen, odometro, fecha_inspeccion, vehiculo_presentado, resultado, puntuacion, observaciones)
VALUES (@uni, @veh, 'F19_REMOLQUE', @tec, 'A000019', NULL, CURDATE(), 'VACIO', 'APROBADO', 100.00, 'Inspección de ejemplo F-19 Remolque.');
SET @insp := LAST_INSERT_ID();
CALL _seed_child('inspeccion_iluminacion', @insp);
CALL _seed_child('inspeccion_suspension',  @insp);
CALL _seed_child('inspeccion_chasis',      @insp);
CALL _seed_child('inspeccion_frenos',      @insp);
CALL _seed_child('inspeccion_sistema_aire',@insp);
CALL _seed_child('inspeccion_carroceria',  @insp);
UPDATE inspeccion_carroceria SET tipo_carroceria='Plataforma' WHERE inspeccion_id=@insp;
INSERT INTO inspeccion_llantas (inspeccion_id, numero_llanta, posicion, profundidad_mm, profundidad_cumple, presion_psi, presion_cumple, banda_rodamiento, costados, rin_condicion, rin_sujetadores, rin_artilleria) VALUES
(@insp,1,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,1,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,5,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,5,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,6,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,6,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE');

-- =====================================================================
-- F-20 DOLLY (D1, 3 grupos: 1/2, 5/6, 7/8)
-- =====================================================================
INSERT INTO vehiculos (propietario_id, niv, placas, marca, ejes, anio, tipo_vehiculo, modalidad, tipo_servicio, folio_tc)
VALUES (@prop, '1D1EJEMPLO0000001', 'EJM-F20', 'EJEMPLO', 1, 2019, 'D1', 'AUTOTRANSPORTE FEDERAL', 'CARGA GENERAL', 'TC-F20');
SET @veh := LAST_INSERT_ID();
INSERT INTO inspecciones (unidad_inspeccion_id, vehiculo_id, tipo_formulario, tecnico_id, folio_dictamen, odometro, fecha_inspeccion, vehiculo_presentado, resultado, puntuacion, observaciones)
VALUES (@uni, @veh, 'F20_DOLLY', @tec, 'A000020', NULL, CURDATE(), 'VACIO', 'APROBADO', 100.00, 'Inspección de ejemplo F-20 Dolly.');
SET @insp := LAST_INSERT_ID();
CALL _seed_child('inspeccion_iluminacion', @insp);
CALL _seed_child('inspeccion_suspension',  @insp);
CALL _seed_child('inspeccion_chasis',      @insp);
CALL _seed_child('inspeccion_frenos',      @insp);
CALL _seed_child('inspeccion_sistema_aire',@insp);
CALL _seed_child('inspeccion_acoplamiento',@insp);
INSERT INTO inspeccion_llantas (inspeccion_id, numero_llanta, posicion, profundidad_mm, profundidad_cumple, presion_psi, presion_cumple, banda_rodamiento, costados, rin_condicion, rin_sujetadores, rin_artilleria) VALUES
(@insp,1,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,5,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,7,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE');

-- =====================================================================
-- F-21 AUTOBÚS (AB, 8 llantas)
-- =====================================================================
INSERT INTO vehiculos (propietario_id, niv, placas, marca, ejes, anio, tipo_vehiculo, modalidad, tipo_servicio, folio_tc)
VALUES (@prop, '2ABEJEMPLO0000001', 'EJM-F21', 'EJEMPLO', 2, 2023, 'AB', 'AUTOTRANSPORTE FEDERAL', 'PASAJE', 'TC-F21');
SET @veh := LAST_INSERT_ID();
INSERT INTO inspecciones (unidad_inspeccion_id, vehiculo_id, tipo_formulario, tecnico_id, folio_dictamen, odometro, fecha_inspeccion, vehiculo_presentado, resultado, puntuacion, tipo_camara_frenado, camara_abrazadera_mm, varilla_ll1_2_mm, varilla_ll1_2_resultado, varilla_ll3_4_mm, varilla_ll3_4_resultado, observaciones)
VALUES (@uni, @veh, 'F21_AUTOBUS', @tec, 'M000021', 210400, CURDATE(), 'VACIO', 'APROBADO', 100.00, 'CAMARA DE FRENO TIPO ABRAZADERA', 2.50, 2.00, 'CUMPLE', 2.10, 'CUMPLE', 'Inspección de ejemplo F-21 Autobús.');
SET @insp := LAST_INSERT_ID();
CALL _seed_child('inspeccion_iluminacion', @insp);
CALL _seed_child('inspeccion_suspension',  @insp);
CALL _seed_child('inspeccion_chasis',      @insp);
CALL _seed_child('inspeccion_frenos',      @insp);
CALL _seed_child('inspeccion_sistema_aire',@insp);
CALL _seed_child('inspeccion_cabina',      @insp);
UPDATE inspeccion_sistema_aire SET caida_presion_psi=1.50, tiempo_carga_min=2.00 WHERE inspeccion_id=@insp;
INSERT INTO inspeccion_llantas (inspeccion_id, numero_llanta, posicion, profundidad_mm, profundidad_cumple, presion_psi, presion_cumple, banda_rodamiento, costados, rin_condicion, rin_sujetadores, rin_artilleria) VALUES
(@insp,1,'EXTERNA', 8.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,1,'INTERNA', 8.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,2,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,3,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'EXTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE'),
(@insp,4,'INTERNA',12.00,'CUMPLE',100.00,'CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE','CUMPLE');

DROP PROCEDURE IF EXISTS _seed_child;
