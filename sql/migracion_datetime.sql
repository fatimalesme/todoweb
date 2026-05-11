-- ============================================================
-- MIGRACIÓN: fecha_limite DATE → DATETIME
-- Ejecutar UNA sola vez sobre la BD existente:
--   mysql -u root -p todoweb_db < sql/migracion_datetime.sql
--
-- Si es una instalación nueva, usa directamente estructura.sql
-- que ya tiene el tipo correcto.
-- ============================================================

USE todoweb_db;

-- Cambiamos el tipo de la columna para que admita hora además de fecha.
-- MySQL convierte los valores DATE existentes añadiendo '00:00:00',
-- así que no se pierde ningún dato.
ALTER TABLE tareas
    MODIFY COLUMN fecha_limite DATETIME DEFAULT NULL;

-- También cambiamos fecha_finalizacion para que quede consistente
-- (ya era DATETIME, pero lo dejamos explícito por si acaso).
ALTER TABLE tareas
    MODIFY COLUMN fecha_finalizacion DATETIME DEFAULT NULL;

-- Índice para acelerar las consultas de avisos y ordenación
-- (si ya existe no da error gracias a IF NOT EXISTS)
ALTER TABLE tareas
    ADD INDEX IF NOT EXISTS idx_fecha_limite (id_usuario, completada, fecha_limite);
