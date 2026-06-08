-- Añade el campo 'apellidos' a la tabla users
ALTER TABLE users ADD COLUMN apellidos VARCHAR(100) NULL AFTER name;