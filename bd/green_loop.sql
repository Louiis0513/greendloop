-- Green Loop — esquema + seed demo (presentación)
-- Credenciales: admin@demo.com / Admin123!

DROP DATABASE IF EXISTS green_loop;
CREATE DATABASE green_loop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE green_loop;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  telefono VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'usuario',
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE productos (
  id_producto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  categoria VARCHAR(50) NOT NULL,
  tipo_publicacion VARCHAR(30) NOT NULL,
  imagen VARCHAR(255) NULL,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE solicitudes (
  id_solicitud INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_producto INT UNSIGNED NOT NULL,
  id_solicitante INT UNSIGNED NOT NULL,
  id_propietario INT UNSIGNED NOT NULL,
  tipo_solicitud VARCHAR(30) NOT NULL,
  mensaje TEXT NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  detalles_negociacion TEXT NULL,
  lugar_encuentro VARCHAR(150) NULL,
  fecha_encuentro DATE NULL,
  hora_encuentro TIME NULL,
  fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
    ON DELETE CASCADE,
  FOREIGN KEY (id_solicitante) REFERENCES usuarios(id_usuario)
    ON DELETE CASCADE,
  FOREIGN KEY (id_propietario) REFERENCES usuarios(id_usuario)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Productos que el solicitante ofrece en un trueque (1 a 5 por solicitud).
-- El propietario elige 1: queda con elegido = 1 al aceptar.
CREATE TABLE solicitud_ofrecimientos (
  id_ofrecimiento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_solicitud INT UNSIGNED NOT NULL,
  id_producto_ofrecido INT UNSIGNED NOT NULL,
  elegido TINYINT(1) NOT NULL DEFAULT 0,
  fecha_ofrecimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_solicitud) REFERENCES solicitudes(id_solicitud)
    ON DELETE CASCADE,
  FOREIGN KEY (id_producto_ofrecido) REFERENCES productos(id_producto)
    ON DELETE CASCADE,
  UNIQUE KEY uq_oferta_por_solicitud (id_solicitud, id_producto_ofrecido)
) ENGINE=InnoDB;

-- Password para todos: Admin123!
-- Hash: password_hash('Admin123!', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) VALUES
('María López',   'admin@demo.com',  '3001112233', '$2y$10$msTQY4AmfJDME2c2OX8Uh.VesH1xc1cQ/3ZxwdJdOyuap.oaFwQyi', 'usuario'),
('Carlos Ruiz',   'carlos@demo.com', '3104445566', '$2y$10$msTQY4AmfJDME2c2OX8Uh.VesH1xc1cQ/3ZxwdJdOyuap.oaFwQyi', 'usuario'),
('Ana Torres',    'ana@demo.com',    '3207778899', '$2y$10$msTQY4AmfJDME2c2OX8Uh.VesH1xc1cQ/3ZxwdJdOyuap.oaFwQyi', 'usuario'),
('Diego Vargas',  'diego@demo.com',  '3012223344', '$2y$10$msTQY4AmfJDME2c2OX8Uh.VesH1xc1cQ/3ZxwdJdOyuap.oaFwQyi', 'usuario');

INSERT INTO productos
  (id_usuario, nombre, descripcion, categoria, tipo_publicacion, imagen, disponible, fecha_publicacion)
VALUES
(1, 'Mochila escolar azul',
 'Mochila en buen estado, varios compartimentos. Ideal para colegio o universidad. Busco intercambiar por una lonchera térmica o un termo.',
 'Otros', 'intercambio', 'img_productos/mochila.jpg', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1, 'Colección Harry Potter (7 tomos)',
 'Saga completa en español, pasta blanda. Algunas páginas con subrayado leve. Se regalan juntas a quien las lea.',
 'Libros', 'regalo', 'img_productos/libros.jpg', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 'Monstera mediana en maceta',
 'Planta sana, con tutor de bambú. Incluye maceta plástica. Perfecta para balcón o sala iluminada.',
 'Hogar', 'regalo', 'img_productos/planta.jpg', 1, DATE_SUB(NOW(), INTERVAL 9 DAY)),
(2, 'Audífonos Bluetooth over-ear',
 'Sonido limpio, batería ~20 h. Un poco de desgaste en la diadema. Cambio por teclado mecánico o mouse.',
 'Tecnología', 'intercambio', 'img_productos/audifonos.jpg', 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(3, 'Lámpara de escritorio LED',
 'Brazo flexible, tres intensidades. Funciona perfecto. La regalo porque me mudé y ya no cabe en el escritorio.',
 'Hogar', 'regalo', 'img_productos/lampara.jpg', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(3, 'Bicicleta urbana rodada 26"',
 'Frenos revisados, candado incluido. Ideal para trayectos cortos. Busco intercambiar por patineta o roller.',
 'Otros', 'intercambio', 'img_productos/bici.jpg', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(4, 'Tenis running talla 42',
 'Usados unas 15 veces, suela en buen estado. Cambio por short deportivo o botella térmica de calidad.',
 'Ropa', 'intercambio', 'img_productos/tenis.jpg', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 'Guitarra acústica principiante',
 'Afinada, con funda básica. Ideal para aprender. Preferible intercambio por ukelele o teclado pequeño.',
 'Otros', 'intercambio', 'img_productos/guitarra.jpg', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'Tablet 10" usada',
 'Sirve para lectura y videos. Sin cargador original (usa USB-C genérico). Se regala por mudanza.',
 'Tecnología', 'regalo', 'img_productos/tablet.jpg', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Juego de mesa familiar',
 'Caja completa, fichas y tablero sin faltantes. Ideal para noches en familia. Regalo a quien lo disfrute.',
 'Juguetes', 'regalo', 'img_productos/juego.jpg', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'Cafetera italiana de aluminio',
 'Para 6 tazas, muy bien cuidada. Busco cambiar por tetera o molino de café manual.',
 'Hogar', 'intercambio', 'img_productos/cafetera.jpg', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'Camisa flannel cuadros M',
 'Algodón suave, casi nueva. Queda grande. Se regala a quien le sirva la talla M.',
 'Ropa', 'regalo', 'img_productos/camisa.jpg', 1, NOW());

INSERT INTO solicitudes
  (id_producto, id_solicitante, id_propietario, tipo_solicitud, mensaje, estado,
   detalles_negociacion, lugar_encuentro, fecha_encuentro, hora_encuentro, fecha_solicitud)
VALUES
(1, 2, 1, 'intercambio',
 '¡Hola María! Me interesa tu mochila. Te puedo ofrecer una lonchera térmica casi nueva.',
 'aceptada',
 'Intercambio confirmado: mochila por lonchera térmica.',
 'Parque principal — entrada norte',
 DATE_ADD(CURDATE(), INTERVAL 3 DAY), '16:30:00',
 DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 1, 2, 'intercambio',
 'Carlos, necesito audífonos para clases virtuales. Tengo un mouse inalámbrico Logitech para cambiar.',
 'pendiente',
 'Propongo encuentro en la biblioteca pública el sábado.',
 'Biblioteca pública — primer piso',
 DATE_ADD(CURDATE(), INTERVAL 5 DAY), '11:00:00',
 DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 3, 1, 'regalo',
 'Me encantaría la saga para mi hermana que está empezando a leer. Puedo pasar por ella cuando digas.',
 'pendiente', NULL, NULL, NULL, NULL,
 DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 4, 3, 'intercambio',
 'Ana, me sirve la bici para ir al SENA. Te ofrezco una patineta en buen estado.',
 'aceptada',
 'Acordamos revisión de frenos juntos antes de entregar.',
 'Estación TransMilenio Calle 100',
 DATE_ADD(CURDATE(), INTERVAL 2 DAY), '18:00:00',
 DATE_SUB(NOW(), INTERVAL 3 DAY)),
(10, 1, 2, 'regalo',
 'Quiero el juego de mesa para una tarde familiar. ¿Sigue disponible?',
 'rechazada', NULL, NULL, NULL, NULL,
 DATE_SUB(NOW(), INTERVAL 4 DAY)),
(11, 2, 3, 'intercambio',
 'Me interesa la cafetera. Tengo un molino manual de café (casi nuevo) para el trueque.',
 'pendiente',
 'Molino marca Hario, usado 3 veces.',
 NULL, NULL, NULL,
 NOW());

-- Ofrecimientos demo: la solicitud 2 (audífonos) ofrece la tablet (9),
-- la 4 (bicicleta) ofrece los tenis (7) y la 6 (cafetera) los audífonos (4).
INSERT INTO solicitud_ofrecimientos (id_solicitud, id_producto_ofrecido, elegido) VALUES
(2, 9, 0),
(4, 7, 0),
(6, 4, 0);
