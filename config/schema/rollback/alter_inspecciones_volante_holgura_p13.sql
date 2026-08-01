-- Rollback P1.3
ALTER TABLE `inspecciones`
  DROP COLUMN IF EXISTS `volante_cm`,
  DROP COLUMN IF EXISTS `holgura_cm`;
