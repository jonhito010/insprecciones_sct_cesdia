-- Opcional: contacto del propietario en inspección (ejecutar una vez).
ALTER TABLE propietarios
  ADD COLUMN correo VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN telefono VARCHAR(20) NULL DEFAULT NULL;
