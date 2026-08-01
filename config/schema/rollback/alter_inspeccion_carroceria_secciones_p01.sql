-- Rollback P0.1 · Quita columnas nuevas de carrocería (conserva legacy).

ALTER TABLE `inspeccion_carroceria`
  DROP COLUMN IF EXISTS `grano_lados_soporte`,
  DROP COLUMN IF EXISTS `grano_piso`,
  DROP COLUMN IF EXISTS `grano_carroceria_remaches`,
  DROP COLUMN IF EXISTS `plataforma_plana`,
  DROP COLUMN IF EXISTS `plataforma_laterales_estacas`,
  DROP COLUMN IF EXISTS `grava_laterales_soporte`,
  DROP COLUMN IF EXISTS `grava_piso`,
  DROP COLUMN IF EXISTS `grava_puertas_tolva`,
  DROP COLUMN IF EXISTS `sujecion_puntos_equipo`,
  DROP COLUMN IF EXISTS `sujecion_condicion_carga`,
  DROP COLUMN IF EXISTS `otro_piso`,
  DROP COLUMN IF EXISTS `otro_puertas`,
  DROP COLUMN IF EXISTS `otro_laterales`,
  DROP COLUMN IF EXISTS `otro_sujetadores_mangueras`;
