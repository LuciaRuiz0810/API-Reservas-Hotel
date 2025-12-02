-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS hotelapi;

-- Usar la base de datos
USE hotelapi;

-- Crear el usuario hotelapi
CREATE USER IF NOT EXISTS 'hotelapi'@'localhost' IDENTIFIED BY 'hotelapi';

-- Dar todos los permisos al usuario sobre la base de datos
GRANT ALL PRIVILEGES ON hotelapi.* TO 'hotelapi'@'localhost';

-- Aplicar los cambios
FLUSH PRIVILEGES;

-- Crear tabla hoteles
CREATE TABLE IF NOT EXISTS hoteles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    estrellas INT NOT NULL CHECK (estrellas >= 1 AND estrellas <= 5),
    precio_noche DECIMAL(10,2) NOT NULL,
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar algunos datos de ejemplo
INSERT INTO hoteles (nombre, ciudad, estrellas, precio_noche, descripcion) VALUES
('Hotel Plaza', 'Madrid', 4, 120.50, 'Hotel céntrico con excelente ubicación'),
('Resort Mar Azul', 'Barcelona', 5, 250.00, 'Resort de lujo frente al mar'),
('Hostal San Miguel', 'Sevilla', 2, 45.00, 'Hostal económico en el centro histórico'),
('Hotel Montaña Verde', 'Granada', 3, 85.75, 'Hotel rural con vistas a la montaña');