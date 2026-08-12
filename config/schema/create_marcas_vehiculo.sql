-- Catálogo de marcas de vehículo (Registros → Marcas).
-- Rollback: config/schema/rollback/create_marcas_vehiculo.sql
-- El valor guardado en vehiculos.marca sigue siendo el nombre (texto), no el id.

CREATE TABLE IF NOT EXISTS `marcas_vehiculo` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_marcas_vehiculo_nombre` (`nombre`),
  KEY `idx_marcas_vehiculo_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
