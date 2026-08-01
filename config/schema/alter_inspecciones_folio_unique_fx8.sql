-- INC-8 · Índice UNIQUE sobre folio_dictamen.
-- ⚠️ NO aplicar hasta limpiar duplicados (ver FIX_HISTORIAL_INMEDIATOS.md).
-- Rollback: config/schema/rollback/alter_inspecciones_folio_unique_fx8.sql

-- Verificar duplicados antes:
-- SELECT folio_dictamen, COUNT(*) c FROM inspecciones
-- GROUP BY folio_dictamen HAVING c > 1;

ALTER TABLE `inspecciones`
  ADD UNIQUE INDEX `uq_inspecciones_folio_dictamen` (`folio_dictamen`);
