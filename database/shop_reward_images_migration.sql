-- Permite asignar una imagen visual a cada recompensa de tienda.

ALTER TABLE rewards
    ADD COLUMN image_path VARCHAR(255) NULL AFTER description;