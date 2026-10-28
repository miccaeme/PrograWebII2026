- 1. Tabla de Preguntas
CREATE TABLE IF NOT EXISTS preguntas (
                                         id INT AUTO_INCREMENT PRIMARY KEY,
                                         id_categoria INT NOT NULL,
                                         enunciado TEXT NOT NULL,
                                         INDEX idx_categoria (id_categoria),
    CONSTRAINT fk_pregunta_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Geek & Tech (id: 1)
INSERT INTO preguntas (id, id_categoria, enunciado) VALUES
                                                        (1, 1, '¿Qué significa la sigla CSS en desarrollo web?'),
                                                        (2, 1, '¿Cuál de estos lenguajes se ejecuta principalmente en el lado del servidor?');

-- Ciencia & Naturaleza (id: 2)
INSERT INTO preguntas (id, id_categoria, enunciado) VALUES
                                                        (3, 2, '¿Cuál es el símbolo químico del oro?'),
                                                        (4, 2, '¿Cuál es el planeta más grande del sistema solar?');
-- Geografía & Mundo (id: 3)
INSERT INTO preguntas (id, id_categoria, enunciado) VALUES
                                                        (5, 3, '¿Cuál es la capital de Australia?'),
                                                        (6, 3, '¿Qué país tiene forma de bota en el mapa de Europa?');
-- Historia & Artes (id: 4)
INSERT INTO preguntas (id, id_categoria, enunciado) VALUES
                                                        (7, 4, '¿Quién pintó la famosa obra "La noche estrellada"?'),
                                                        (8, 4, '¿En qué año llegó el ser humano a la Luna por primera vez?');
-- Deportes (id: 5)
INSERT INTO preguntas (id, id_categoria, enunciado) VALUES
                                                        (9, 5, '¿Cuántos jugadores forman un equipo titular de fútbol en cancha?'),
                                                        (10, 5, '¿Qué país ganó la Copa Mundial de la FIFA en Qatar 2022?');

