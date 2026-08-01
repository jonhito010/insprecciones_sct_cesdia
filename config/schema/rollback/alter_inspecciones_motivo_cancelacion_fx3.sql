-- Rollback INC-3 · motivo_cancelacion
ALTER TABLE `inspecciones`
  DROP COLUMN IF EXISTS `motivo_cancelacion`;
