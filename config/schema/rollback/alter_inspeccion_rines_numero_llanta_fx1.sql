-- Rollback FIX1 · Rines por llanta individual.
-- ADVERTENCIA: la expansión N-M → 2 filas no se reconstruye fielmente.
-- Se eliminan filas con numero_llanta y se deja el esquema listo para el enum legado.
-- Si aún existen filas solo con par_rines, se conservan.

ALTER TABLE `inspeccion_rines` DROP INDEX IF EXISTS `uq_rin_llanta`;

DELETE FROM `inspeccion_rines`
WHERE `numero_llanta` IS NOT NULL
  AND (`par_rines` IS NULL OR `par_rines` = '');

ALTER TABLE `inspeccion_rines`
  DROP COLUMN IF EXISTS `numero_llanta`;

-- Restaurar NOT NULL en par_rines solo si no quedan NULL
-- (si hay NULL residuales, el MODIFY fallará a propósito — revisar datos).
ALTER TABLE `inspeccion_rines`
  MODIFY COLUMN `par_rines` enum('1-2','3-4','5-6','7-8','9-10','11-12')
    NOT NULL COMMENT 'Par de rines — hasta 12 en remolque F-19';
