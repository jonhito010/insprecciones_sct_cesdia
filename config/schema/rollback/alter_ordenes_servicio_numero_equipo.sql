-- Rollback: quitar número de equipo en órdenes de servicio.
ALTER TABLE `ordenes_servicio`
  DROP COLUMN IF EXISTS `numero_equipo`;
