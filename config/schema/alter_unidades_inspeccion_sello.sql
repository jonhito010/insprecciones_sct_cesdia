-- Ruta pública del sello / representante UV (ej. /uploads/sellos/unidad_1_a1b2c3d4.png)
-- Solo si la columna aún no existe.
ALTER TABLE unidades_inspeccion
  ADD COLUMN pathSello VARCHAR(255) NULL DEFAULT NULL;
