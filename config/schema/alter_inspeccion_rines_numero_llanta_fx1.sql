-- FIX1 · Rines por llanta individual (numero_llanta).
-- Rollback: config/schema/rollback/alter_inspeccion_rines_numero_llanta_fx1.sql
--
-- Agrega numero_llanta (1–12), deja par_rines como columna deprecada (nullable)
-- y expande filas legacy par='N-M' a 2 filas (N y M) con los mismos valores.

-- 1) Columna nueva
SET @has_num := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inspeccion_rines'
      AND COLUMN_NAME = 'numero_llanta'
);
SET @sql := IF(
    @has_num = 0,
    'ALTER TABLE `inspeccion_rines` ADD COLUMN `numero_llanta` TINYINT UNSIGNED NULL COMMENT ''Llanta individual 1-12 (formato oficial)'' AFTER `par_rines`',
    'SELECT ''numero_llanta ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) par_rines nullable (deprecada; filas nuevas van con NULL)
ALTER TABLE `inspeccion_rines`
  MODIFY COLUMN `par_rines` enum('1-2','3-4','5-6','7-8','9-10','11-12')
    NULL COMMENT 'DEPRECATED: usar numero_llanta; legado por par';

-- 3) Expandir filas legacy (solo las que aún no tienen numero_llanta)
DROP TEMPORARY TABLE IF EXISTS `_fx1_rines_expand`;
CREATE TEMPORARY TABLE `_fx1_rines_expand` AS
SELECT
    `id`,
    `inspeccion_id`,
    `par_rines`,
    `num_sujetadores`,
    `sujetadores_cumple`,
    `maza_cumple`,
    `balero_cumple`
FROM `inspeccion_rines`
WHERE `par_rines` IS NOT NULL
  AND `par_rines` <> ''
  AND `numero_llanta` IS NULL;

SELECT COUNT(*) AS fx1_filas_legacy_a_expandir FROM `_fx1_rines_expand`;

-- Dos INSERT (MySQL no permite reabrir la misma TEMPORARY TABLE en un UNION).
INSERT INTO `inspeccion_rines` (
    `inspeccion_id`, `par_rines`, `numero_llanta`,
    `num_sujetadores`, `sujetadores_cumple`, `maza_cumple`, `balero_cumple`
)
SELECT
    e.`inspeccion_id`,
    NULL,
    CAST(SUBSTRING_INDEX(e.`par_rines`, '-', 1) AS UNSIGNED),
    e.`num_sujetadores`,
    e.`sujetadores_cumple`,
    e.`maza_cumple`,
    e.`balero_cumple`
FROM `_fx1_rines_expand` e;

INSERT INTO `inspeccion_rines` (
    `inspeccion_id`, `par_rines`, `numero_llanta`,
    `num_sujetadores`, `sujetadores_cumple`, `maza_cumple`, `balero_cumple`
)
SELECT
    e.`inspeccion_id`,
    NULL,
    CAST(SUBSTRING_INDEX(e.`par_rines`, '-', -1) AS UNSIGNED),
    e.`num_sujetadores`,
    e.`sujetadores_cumple`,
    e.`maza_cumple`,
    e.`balero_cumple`
FROM `_fx1_rines_expand` e;

DELETE r
FROM `inspeccion_rines` r
INNER JOIN `_fx1_rines_expand` e ON e.`id` = r.`id`;

SELECT ROW_COUNT() AS fx1_filas_legacy_eliminadas_tras_expandir;

DROP TEMPORARY TABLE IF EXISTS `_fx1_rines_expand`;

-- 4) Índice único por llanta (idempotente)
SET @has_uq := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inspeccion_rines'
      AND INDEX_NAME = 'uq_rin_llanta'
);
SET @sql := IF(
    @has_uq = 0,
    'ALTER TABLE `inspeccion_rines` ADD UNIQUE KEY `uq_rin_llanta` (`inspeccion_id`, `numero_llanta`)',
    'SELECT ''uq_rin_llanta ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
