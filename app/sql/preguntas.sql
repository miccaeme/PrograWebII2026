USE PrograWebII;

CREATE TABLE IF NOT EXISTS preguntas(
    id INT AUTO_INCREMENT PRIMARY KEY,
    enunciado VARCHAR(255) NOT NULL,
    id_categoria INT NOT NULL,
    estado ENUM('pendiente', 'activa', 'rechazada', 'inactiva') NOT NULL DEFAULT 'pendiente',
    id_usuario_creador INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    veces_respondida INT NOT NULL DEFAULT 0,
    veces_acertada INT NOT NULL DEFAULT 0,
    INDEX idx_categoria (id_categoria),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id),
    FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Preguntas de ejemplo (activas, cargadas por el sistema: sin creador)
INSERT IGNORE INTO preguntas (id, id_categoria, enunciado, estado) VALUES
    (1, 1, '¿Qué significa la sigla CSS en desarrollo web?', 'activa'),
    (2, 1, '¿Cuál de estos lenguajes se ejecuta principalmente en el lado del servidor?', 'activa'),
    (3, 2, '¿Cuál es el símbolo químico del oro?', 'activa'),
    (4, 2, '¿Cuál es el planeta más grande del sistema solar?', 'activa'),
    (5, 3, '¿Cuál es la capital de Australia?', 'activa'),
    (6, 3, '¿Qué país tiene forma de bota en el mapa de Europa?', 'activa'),
    (7, 4, '¿Quién pintó la famosa obra "La noche estrellada"?', 'activa'),
    (8, 4, '¿En qué año llegó el ser humano a la Luna por primera vez?', 'activa'),
    (9, 5, '¿Cuántos jugadores forman un equipo titular de fútbol en cancha?', 'activa'),
    (10, 5, '¿Qué país ganó la Copa Mundial de la FIFA en Qatar 2022?', 'activa');