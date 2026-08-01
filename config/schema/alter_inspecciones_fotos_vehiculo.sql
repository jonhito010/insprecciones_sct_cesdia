-- Ejecutar una vez en la base `inspecciones` (MySQL/MariaDB).
-- Rutas relativas web guardadas en BD, archivos bajo webroot/uploads/inspecciones/{id}/

ALTER TABLE inspecciones
  ADD COLUMN foto_vehiculo_1 VARCHAR(512) NULL DEFAULT NULL,
  ADD COLUMN foto_vehiculo_2 VARCHAR(512) NULL DEFAULT NULL;
