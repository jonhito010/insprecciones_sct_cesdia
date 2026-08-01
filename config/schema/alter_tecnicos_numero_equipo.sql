-- Número de equipo del técnico (equipo con que inspecciona). Solo si aún no existe la columna.
ALTER TABLE tecnicos
  ADD COLUMN numero_equipo VARCHAR(25) NULL DEFAULT NULL;
