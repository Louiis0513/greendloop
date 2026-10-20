-- Green Loop — migración para BD ya importada (no borra datos)
-- Ejecutar una sola vez: mysql -u root green_loop < bd/migracion_trueque.sql

USE green_loop;

CREATE TABLE IF NOT EXISTS solicitud_ofrecimientos (
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

-- Ofrecimientos demo (solo si existen esas solicitudes del seed)
INSERT IGNORE INTO solicitud_ofrecimientos (id_solicitud, id_producto_ofrecido, elegido) VALUES
(2, 9, 0),
(4, 7, 0),
(6, 4, 0);
