-- ============================================
-- ejemplotokens — Schema completo
-- Laboratorio JWT + API REST PHP
-- Desarrollo de Software VII
-- ============================================

CREATE DATABASE IF NOT EXISTS ejemplotokens
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ejemplotokens;

-- --------------------------------------------
-- Tabla: usuarios
-- Almacena las credenciales de acceso.
-- La contraseña NUNCA se guarda en texto plano,
-- siempre se usa password_hash() con BCRYPT.
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    creado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------
-- Tabla: productos
-- Catálogo de productos del inventario.
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    codigo    VARCHAR(20)   NOT NULL,
    producto  VARCHAR(100)  NOT NULL,
    precio    DECIMAL(10,2) NOT NULL,
    cantidad  INT           NOT NULL,
    creado_en TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

