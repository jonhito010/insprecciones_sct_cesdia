-- Rollback INC-8 · UNIQUE folio_dictamen
ALTER TABLE `inspecciones`
  DROP INDEX `uq_inspecciones_folio_dictamen`;
