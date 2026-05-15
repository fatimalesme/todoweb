-- base de datos todoweb
-- hay que importar esto antes de usar la app
-- desde phpMyAdmin o con: mysql -u root -p < sql/estructura.sql

CREATE DATABASE IF NOT EXISTS todoweb_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE todoweb_db;

-- Tabla de usuarios
-- La columna password almacena el hash generado por password_hash()

CREATE TABLE IF NOT EXISTS usuarios (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(30)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    rol        ENUM('user', 'guest') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de listas de tareas
-- Cada lista pertenece a un usuario
-- Si se borra el usuario, se borran sus listas en cascada
CREATE TABLE IF NOT EXISTS listas (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT          NOT NULL,
    nombre     VARCHAR(100) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de tareas
-- id_lista = 0 significa que la tarea pertenece a "Mi Dia"

CREATE TABLE IF NOT EXISTS tareas (
    id                 INT          AUTO_INCREMENT PRIMARY KEY,
    id_usuario         INT          NOT NULL,
    id_lista           INT          NOT NULL DEFAULT 0,
    texto              VARCHAR(500) NOT NULL,
    descripcion        TEXT         DEFAULT NULL,
    pomodoro_segundos  INT          NOT NULL DEFAULT 0,
    completada         TINYINT(1)   NOT NULL DEFAULT 0,
    fecha_alta   DATETIME,
    fecha_limite DATETIME,
    fecha_finalizacion DATETIME     DEFAULT NULL,
    postergaciones     INT          NOT NULL DEFAULT 0,
    max_postergaciones INT          NOT NULL DEFAULT 3,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

