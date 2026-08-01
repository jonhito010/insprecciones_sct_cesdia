-- Ejecutar solo las sentencias que falten en tu base.
ALTER TABLE unidades_inspeccion
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE unidades_inspeccion
  ADD COLUMN aprobacion VARCHAR(255) NULL DEFAULT NULL;
