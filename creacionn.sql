-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS API;

-- Usar la base de datos
USE API;

-- Crear el usuario webservice
CREATE USER IF NOT EXISTS 'webservice'@'localhost' IDENTIFIED BY 'webservice';

-- Dar todos los permisos al usuario sobre la base de datos
GRANT ALL PRIVILEGES ON API.* TO 'webservice'@'localhost';

-- Aplicar los cambios
FLUSH PRIVILEGES;

-- Crear tabla clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(200) NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20)
);

-- Crear tabla habitaciones
CREATE TABLE IF NOT EXISTS habitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL UNIQUE,
    planta INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    suite BOOLEAN DEFAULT FALSE,
    num_personas INT NOT NULL
);

-- Crear tabla reservas
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    habitacion_id INT NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    precio_total DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'activa',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE CASCADE
);

-- Insertar datos de ejemplo para clientes
INSERT INTO clientes (nombre, apellidos, dni, correo, telefono) VALUES
('Juan', 'Pérez García', '12345678A', 'juan.perez@email.com', '666123456'),
('María', 'López Martínez', '87654321B', 'maria.lopez@email.com', '677234567'),
('Carlos', 'Rodríguez Sánchez', '11223344C', 'carlos.rodriguez@email.com', '688345678'),
('Ana', 'González Fernández', '44332211D', 'ana.gonzalez@email.com', '699456789');

-- Insertar datos de ejemplo para habitaciones
INSERT INTO habitaciones (numero, planta, tipo, precio, suite, num_personas) VALUES
(101, 1, 'Individual', 80.00, FALSE, 1),
(102, 1, 'Doble', 120.00, FALSE, 2),
(103, 1, 'Triple', 150.00, FALSE, 3),
(201, 2, 'Doble', 130.00, FALSE, 2),
(202, 2, 'Suite', 250.00, TRUE, 4),
(203, 2, 'Familiar', 180.00, FALSE, 4),
(301, 3, 'Suite Presidencial', 500.00, TRUE, 6),
(302, 3, 'Doble Deluxe', 180.00, FALSE, 2);

-- Insertar datos de ejemplo para reservas
INSERT INTO reservas (cliente_id, habitacion_id, fecha_entrada, fecha_salida, precio_total) VALUES
(1, 1, '2025-01-15', '2025-01-18', 240.00),
(2, 2, '2025-01-20', '2025-01-25', 600.00),
(3, 5, '2025-02-01', '2025-02-05', 1000.00),
(4, 6, '2025-02-10', '2025-02-12', 360.00);
