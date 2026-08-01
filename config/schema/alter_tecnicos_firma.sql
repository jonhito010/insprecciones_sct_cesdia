-- Ruta pública de la firma PNG (ej. /uploads/firmas/tecnico_1_a1b2c3d4.png)
-- Solo si la columna aún no existe (en muchos entornos ya existe como pathFirma).
ALTER TABLE tecnicos
  ADD COLUMN pathFirma VARCHAR(255) NULL DEFAULT NULL;
