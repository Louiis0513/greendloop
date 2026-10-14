CREATE DATABASE IF NOT EXISTS green_loop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE green_loop;

CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'usuario',
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS productos (
  id_producto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  categoria VARCHAR(50) NOT NULL,
  tipo_publicacion ENUM('regalo', 'intercambio') NOT NULL,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitudes (
  id_solicitud INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_producto INT UNSIGNED NOT NULL,
  id_solicitante INT UNSIGNED NOT NULL,
  id_propietario INT UNSIGNED NOT NULL,
  tipo_solicitud ENUM('regalo', 'intercambio') NOT NULL,
  mensaje TEXT NOT NULL,
  estado ENUM('pendiente', 'aceptada', 'rechazada', 'completada') NOT NULL DEFAULT 'pendiente',
  detalles_negociacion TEXT NULL,
  lugar_encuentro VARCHAR(150) NULL,
  fecha_encuentro DATE NULL,
  hora_encuentro TIME NULL,
  fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
  FOREIGN KEY (id_solicitante) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_propietario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;
