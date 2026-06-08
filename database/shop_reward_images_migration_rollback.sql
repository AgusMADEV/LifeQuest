-- Rollback de imagenes en recompensas de tienda.

ALTER TABLE rewards
    DROP COLUMN image_path;