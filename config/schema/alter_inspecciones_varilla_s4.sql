-- Pares de varilla 13-14 y 15-16 para F-19 S4 (16 llantas).
-- Ejecutar solo si aún no existen las columnas.

ALTER TABLE `inspecciones`
  ADD COLUMN `varilla_ll13_14_mm` DECIMAL(6,2) NULL DEFAULT NULL,
  ADD COLUMN `varilla_ll13_14_resultado` VARCHAR(20) NULL DEFAULT NULL,
  ADD COLUMN `varilla_ll15_16_mm` DECIMAL(6,2) NULL DEFAULT NULL,
  ADD COLUMN `varilla_ll15_16_resultado` VARCHAR(20) NULL DEFAULT NULL;
