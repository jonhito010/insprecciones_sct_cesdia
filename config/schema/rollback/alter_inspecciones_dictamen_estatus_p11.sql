-- Rollback P1.1
ALTER TABLE `inspecciones`
  DROP COLUMN IF EXISTS `dictamen`,
  DROP COLUMN IF EXISTS `estatus_registro`;
