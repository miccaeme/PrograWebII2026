USE PrograWebII;

CREATE TABLE IF NOT EXISTS categorias(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NULL,
    color CHAR(7) NOT NULL,
    icono VARCHAR(50) NOT NULL DEFAULT 'cerebro-default'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categorias (id, nombre, descripcion, color, icono) VALUES
    (1, 'Geek & Tech', 'Videojuegos, programación y cultura pop', '#6655d9', 'cerebro-geek'),
    (2, 'Ciencia & Naturaleza', 'Física, química, biología y astronomía', '#10b981', 'cerebro-ciencia'),
    (3, 'Geografía & Mundo', 'Países, banderas, capitales y mapas', '#f59e0b', 'cerebro-mapa'),
    (4, 'Historia & Arte', 'Eventos históricos, pintura y literatura', '#ef4444', 'cerebro-arte'),
    (5, 'Deportes', 'Fútbol, récords y juegos olímpicos', '#3b82f6', 'cerebro-deporte')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    color = VALUES(color),
    icono = VALUES(icono);
