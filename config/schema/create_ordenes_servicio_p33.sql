-- P3.3 · Entidad F-04 Orden de servicio.
-- Rollback: config/schema/rollback/create_ordenes_servicio_p33.sql

CREATE TABLE IF NOT EXISTS `ordenes_servicio` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `inspeccion_id` int unsigned DEFAULT NULL,
  `propietario_id` int unsigned NOT NULL,
  `vehiculo_id` int unsigned NOT NULL,
  `unidad_inspeccion_id` int unsigned NOT NULL,
  `fecha_contrato` date NOT NULL,
  `estatus` enum('BORRADOR','EMITIDA','CANCELADA') NOT NULL DEFAULT 'BORRADOR',
  `firma_solicitante` varchar(255) DEFAULT NULL,
  `firma_prestador` varchar(255) DEFAULT NULL,
  `notas` text,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_os_inspeccion` (`inspeccion_id`),
  KEY `idx_os_propietario` (`propietario_id`),
  KEY `idx_os_vehiculo` (`vehiculo_id`),
  KEY `idx_os_unidad` (`unidad_inspeccion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
