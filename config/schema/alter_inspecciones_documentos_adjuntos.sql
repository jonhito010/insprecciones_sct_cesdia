-- Ejecutar una vez en la base `inspecciones` (MySQL/MariaDB).
-- Documentos opcionales: dictamen/lista de inspección anterior; tarjeta de circulación o factura.

ALTER TABLE inspecciones
  ADD COLUMN doc_inspeccion_anterior VARCHAR(512) NULL DEFAULT NULL,
  ADD COLUMN doc_tarjeta_factura VARCHAR(512) NULL DEFAULT NULL;
