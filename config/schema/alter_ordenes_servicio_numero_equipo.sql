-- Número de máquina/equipo con que se inspecciona (F-04 orden de servicio).
ALTER TABLE `ordenes_servicio`
  ADD COLUMN `numero_equipo` VARCHAR(25) NULL DEFAULT NULL AFTER `unidad_inspeccion_id`;
