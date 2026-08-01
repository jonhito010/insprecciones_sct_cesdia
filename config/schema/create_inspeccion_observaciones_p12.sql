-- P1.2 · Observaciones estructuradas (6 filas PUNTO NOM + REQUISITO).
-- Rollback: config/schema/rollback/create_inspeccion_observaciones_p12.sql

CREATE TABLE IF NOT EXISTS `inspeccion_observaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `inspeccion_id` int unsigned NOT NULL,
  `punto_nom` varchar(10) DEFAULT NULL,
  `requisito` text,
  `orden` tinyint unsigned NOT NULL DEFAULT 1,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_insp_obs_inspeccion` (`inspeccion_id`),
  KEY `idx_insp_obs_orden` (`inspeccion_id`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
