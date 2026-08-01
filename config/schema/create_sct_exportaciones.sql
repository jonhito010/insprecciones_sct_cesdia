-- Bitácora de exportaciones CSV (formato SCT). Ejecutar si aún no existe la tabla.
CREATE TABLE IF NOT EXISTS sct_exportaciones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  inspeccion_id INT UNSIGNED NOT NULL,
  fecha_exportacion DATETIME NOT NULL,
  usuario VARCHAR(120) NOT NULL,
  archivo VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY inspeccion_id (inspeccion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
