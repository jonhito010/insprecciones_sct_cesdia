-- Amplía parabrisas_tipo para AS-1 / AS 10 / NO CUMPLE (antes ENUM Cumple/No cumple/N/A).
ALTER TABLE inspeccion_iluminacion
  MODIFY parabrisas_tipo VARCHAR(20) NULL DEFAULT NULL;
