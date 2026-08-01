-- Ejecutar en la base de datos de la aplicación (una vez).
-- Si `rol` ya existe (p. ej. tras create_admin), omita el primer ALTER.
-- Usuarios existentes sin rol explícito pueden normalizarse así:
-- UPDATE users SET rol = 'admin' WHERE rol IS NULL OR rol = '';

ALTER TABLE users
  ADD COLUMN rol VARCHAR(20) NOT NULL DEFAULT 'admin';

ALTER TABLE users
  ADD COLUMN tecnico_id INT UNSIGNED NULL DEFAULT NULL,
  ADD KEY idx_users_tecnico (tecnico_id);
