-- =====================================================================
--  LogiTrans Express - Sistema Web de Control de Rutas y Transporte
--  Base de datos única: logitrans
--  Motor: MySQL / MariaDB  (XAMPP)
--
--  Contenido:
--    1. usuarios
--    2. conductores
--    3. vehiculos
--    4. rutas
--    5. programaciones_viajes
--    6. seguimientos_viajes
--
--  Uso:
--    mysql -u root < db/logitrans.sql
--    o importar este archivo desde phpMyAdmin.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS logitrans
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE logitrans;

-- Las tablas se recrean para que el script sea reutilizable.
DROP TABLE IF EXISTS seguimientos_viajes;
DROP TABLE IF EXISTS programaciones_viajes;
DROP TABLE IF EXISTS rutas;
DROP TABLE IF EXISTS vehiculos;
DROP TABLE IF EXISTS conductores;
DROP TABLE IF EXISTS usuarios;

-- ---------------------------------------------------------------------
-- 1. Usuarios (autenticación)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(120) NOT NULL,
  correo        VARCHAR(150) NOT NULL,
  usuario       VARCHAR(60)  NOT NULL,
  contrasena    VARCHAR(255) NOT NULL,
  rol           ENUM('administrador','logistica','operador') NOT NULL DEFAULT 'operador',
  token         VARCHAR(64)  NULL DEFAULT NULL,
  sesion_activa TINYINT(1)   NOT NULL DEFAULT 0,
  estado        ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_correo  (correo),
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY idx_usuarios_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Conductores (ms-conductores)
-- ---------------------------------------------------------------------
CREATE TABLE conductores (
  id                         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombres                    VARCHAR(120) NOT NULL,
  apellidos                  VARCHAR(120) NOT NULL,
  documento                  VARCHAR(30)  NOT NULL,
  telefono                   VARCHAR(30)  NOT NULL,
  correo                     VARCHAR(150) NOT NULL,
  numero_licencia            VARCHAR(50)  NOT NULL,
  categoria_licencia         VARCHAR(10)  NOT NULL,
  fecha_vencimiento_licencia DATE         NOT NULL,
  estado                     ENUM('disponible','en_ruta','inactivo') NOT NULL DEFAULT 'disponible',
  created_at                 TIMESTAMP NULL DEFAULT NULL,
  updated_at                 TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_conductores_documento (documento),
  UNIQUE KEY uq_conductores_correo    (correo),
  UNIQUE KEY uq_conductores_licencia  (numero_licencia),
  KEY idx_conductores_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Vehículos (ms-vehiculos)
-- ---------------------------------------------------------------------
CREATE TABLE vehiculos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  placa           VARCHAR(10)  NOT NULL,
  tipo_vehiculo   VARCHAR(60)  NOT NULL,
  capacidad_carga DECIMAL(10,2) NOT NULL,
  modelo          VARCHAR(60)  NOT NULL,
  marca           VARCHAR(60)  NOT NULL,
  estado          ENUM('disponible','en_ruta','mantenimiento','inactivo') NOT NULL DEFAULT 'disponible',
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vehiculos_placa (placa),
  KEY idx_vehiculos_estado (estado),
  KEY idx_vehiculos_tipo   (tipo_vehiculo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Rutas (ms-rutas)
--    La regla "ruta duplicada" se valida en PHP: no hay UNIQUE.
-- ---------------------------------------------------------------------
CREATE TABLE rutas (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ciudad_origen   VARCHAR(100) NOT NULL,
  ciudad_destino  VARCHAR(100) NOT NULL,
  distancia       DECIMAL(10,2) NOT NULL,
  tiempo_estimado VARCHAR(50)  NOT NULL,
  observaciones   TEXT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_rutas_origen  (ciudad_origen),
  KEY idx_rutas_destino (ciudad_destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Programaciones de viajes (ms-rutas)
--    Referencias lógicas a conductores, vehiculos y rutas (sin FK).
-- ---------------------------------------------------------------------
CREATE TABLE programaciones_viajes (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  conductor_id           INT UNSIGNED NOT NULL,
  vehiculo_id            INT UNSIGNED NOT NULL,
  ruta_id                INT UNSIGNED NOT NULL,
  fecha_salida           DATE NOT NULL,
  hora_salida            TIME NOT NULL,
  fecha_estimada_llegada DATE NOT NULL,
  observaciones          TEXT NULL,
  estado                 ENUM('programado','en_transito','retrasado','finalizado','cancelado') NOT NULL DEFAULT 'programado',
  created_at             TIMESTAMP NULL DEFAULT NULL,
  updated_at             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_programaciones_conductor (conductor_id),
  KEY idx_programaciones_vehiculo  (vehiculo_id),
  KEY idx_programaciones_ruta      (ruta_id),
  KEY idx_programaciones_estado    (estado),
  KEY idx_programaciones_fecha     (fecha_salida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Seguimientos de viajes (ms-viajes)
-- ---------------------------------------------------------------------
CREATE TABLE seguimientos_viajes (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  programacion_viaje_id  INT UNSIGNED NOT NULL,
  fecha                  DATE NOT NULL,
  hora                   TIME NOT NULL,
  estado                 ENUM('programado','en_transito','retrasado','finalizado','cancelado') NOT NULL,
  novedad                TEXT NOT NULL,
  created_at             TIMESTAMP NULL DEFAULT NULL,
  updated_at             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_seguimientos_programacion (programacion_viaje_id),
  KEY idx_seguimientos_fecha        (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  Datos de prueba
--  Contraseñas: admin123 / logistica123  (bcrypt, password_hash)
-- =====================================================================

INSERT INTO usuarios (nombre, correo, usuario, contrasena, rol, sesion_activa, estado, created_at, updated_at) VALUES
('Administrador General', 'admin@logitrans.com', 'admin',
 '$2y$10$9IptW3JBgY/deH6mkSXnpeorIXW8qrwtjMSoTbDJT3jf6DJbCwsHC',
 'administrador', 0, 'activo', NOW(), NOW()),
('Laura Logística', 'logistica@logitrans.com', 'logistica',
 '$2y$10$5s4PvpMhha78Dg8GkN00r.0TjvJdJKfdirPkJABpsGDCQ8eokQVsy',
 'logistica', 0, 'activo', NOW(), NOW());

INSERT INTO conductores (nombres, apellidos, documento, telefono, correo, numero_licencia, categoria_licencia, fecha_vencimiento_licencia, estado, created_at, updated_at) VALUES
('Carlos Andrés', 'Ramírez', '10234567', '3001234567', 'carlos.ramirez@logitrans.com', 'LIC-10234567', 'C2', '2028-05-30', 'disponible', NOW(), NOW()),
('María Fernanda', 'Gómez', '10987654', '3109876543', 'maria.gomez@logitrans.com', 'LIC-10987654', 'C3', '2027-11-15', 'en_ruta', NOW(), NOW()),
('Jorge Eliécer', 'Torres', '79543210', '3205557788', 'jorge.torres@logitrans.com', 'LIC-79543210', 'C2', '2029-02-10', 'inactivo', NOW(), NOW());

INSERT INTO vehiculos (placa, tipo_vehiculo, capacidad_carga, modelo, marca, estado, created_at, updated_at) VALUES
('ABC123', 'Camión', 12.50, '2020', 'Chevrolet', 'disponible', NOW(), NOW()),
('XYZ789', 'Tractomula', 30.00, '2019', 'Kenworth', 'en_ruta', NOW(), NOW()),
('KLM456', 'Furgón', 3.50, '2021', 'Renault', 'mantenimiento', NOW(), NOW());

INSERT INTO rutas (ciudad_origen, ciudad_destino, distancia, tiempo_estimado, observaciones, created_at, updated_at) VALUES
('Bogotá', 'Medellín', 415.00, '8 horas', 'Ruta por la autopista Medellín', NOW(), NOW()),
('Medellín', 'Cali', 420.00, '9 horas', NULL, NOW(), NOW()),
('Bogotá', 'Barranquilla', 1000.00, '18 horas', 'Ruta con peajes', NOW(), NOW());

INSERT INTO programaciones_viajes (conductor_id, vehiculo_id, ruta_id, fecha_salida, hora_salida, fecha_estimada_llegada, observaciones, estado, created_at, updated_at) VALUES
(1, 1, 1, '2026-11-20', '06:00:00', '2026-11-20', 'Salida puntual desde el centro de carga', 'programado', NOW(), NOW()),
(2, 2, 3, '2026-11-19', '04:30:00', '2026-11-19', 'Carga pesada, verificar documentación', 'en_transito', NOW(), NOW());

INSERT INTO seguimientos_viajes (programacion_viaje_id, fecha, hora, estado, novedad, created_at, updated_at) VALUES
(2, '2026-11-19', '05:00:00', 'en_transito', 'Viaje iniciado sin novedades.', NOW(), NOW());
