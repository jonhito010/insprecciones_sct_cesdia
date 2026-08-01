-- Última actividad en la app (se actualiza en sesión, ver AppController).
ALTER TABLE users
  ADD COLUMN ultimo_acceso DATETIME NULL DEFAULT NULL;
