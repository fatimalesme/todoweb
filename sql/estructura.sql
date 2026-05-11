-- ============================================================
-- Base de datos: todoweb_db
-- Ejecutar este archivo antes de usar la aplicacion:
--   mysql -u root -p < sql/estructura.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS todoweb_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE todoweb_db;

-- ------------------------------------------------------------
-- Tabla de usuarios
-- La columna password almacena el hash generado por password_hash()
-- NUNCA guardar contrasenas en texto plano
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(30)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    rol        ENUM('user', 'admin', 'guest') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabla de listas de tareas
-- Cada lista pertenece a un usuario
-- Si se borra el usuario, se borran sus listas en cascada
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS listas (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT          NOT NULL,
    nombre     VARCHAR(100) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabla de tareas
-- id_lista = 0 significa que la tarea pertenece a "Mi Dia"
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tareas (
    id                 INT          AUTO_INCREMENT PRIMARY KEY,
    id_usuario         INT          NOT NULL,
    id_lista           INT          NOT NULL DEFAULT 0,
    texto              VARCHAR(500) NOT NULL,
    descripcion        TEXT         DEFAULT NULL,
    pomodoro_segundos  INT          NOT NULL DEFAULT 0,
    completada         TINYINT(1)   NOT NULL DEFAULT 0,
    fecha_alta         DATE,
    fecha_limite       DATE,
    fecha_finalizacion DATETIME     DEFAULT NULL,
    postergaciones     INT          NOT NULL DEFAULT 0,
    max_postergaciones INT          NOT NULL DEFAULT 3,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Usuario de prueba para desarrollo
-- Usuario: admin   Contrasena: admin123
-- IMPORTANTE: cambiar o eliminar este usuario en produccion
-- El hash se genero con: password_hash('admin123', PASSWORD_DEFAULT)
-- ------------------------------------------------------------
INSERT INTO usuarios (username, password, rol) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
