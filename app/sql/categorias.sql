USE PrograWebII;

CREATE TABLE IF NOT EXISTS categorias(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL UNIQUE,
    color CHAR(7) NOT NULL,
    icono VARCHAR(100) NOT NULL
);


INSERT IGNORE INTO categorias (nombre, color, icono) VALUES
    ('Historia', '#E0A458', '🏛️'),
    ('Geografía', '#3BB8A8', '🌍'),
    ('Ciencia y naturaleza', '#5CB85C', '🔬'),
    ('Deportes', '#4A90E2', '⚽'),
    ('Arte y literatura', '#E86A9E', '🎨'),
    ('Cine y TV', '#A98BEA', '🎬');