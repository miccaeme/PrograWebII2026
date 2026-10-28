CREATE TABLE IF NOT EXISTS respuestas (
                                          id INT AUTO_INCREMENT PRIMARY KEY,
                                          id_pregunta INT NOT NULL,
                                          texto VARCHAR(255) NOT NULL,
    es_correcta TINYINT(1) DEFAULT 0,
    INDEX idx_pregunta (id_pregunta),
    CONSTRAINT fk_respuesta_pregunta FOREIGN KEY (id_pregunta) REFERENCES preguntas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Geek & Tech (id: 1)
INSERT INTO respuestas (id_pregunta, texto, es_correcta) VALUES
                                                             (1, 'Cascading Style Sheets', 1),
                                                             (1, 'Computer System Syntax', 0),
                                                             (1, 'Creative Styling System', 0),
                                                             (1, 'Central Sheet Server', 0),
                                                             (2, 'PHP', 1),
                                                             (2, 'HTML', 0),
                                                             (2, 'CSS', 0),
                                                             (2, 'XML', 0);

-- Ciencia & Naturaleza (id: 2)
INSERT INTO respuestas (id_pregunta, texto, es_correcta) VALUES
                                                             (3, 'Au', 1),
                                                             (3, 'Ag', 0),
                                                             (3, 'Fe', 0),
                                                             (3, 'Cu', 0),
                                                             (4, 'Júpiter', 1),
                                                             (4, 'Saturno', 0),
                                                             (4, 'Marte', 0),
                                                             (4, 'Neptuno', 0);

-- Geografía & Mundo (id: 3)
INSERT INTO respuestas (id_pregunta, texto, es_correcta) VALUES
                                                             (5, 'Canberra', 1),
                                                             (5, 'Sídney', 0),
                                                             (5, 'Melbourne', 0),
                                                             (5, 'Brisbane', 0),
                                                             (6, 'Italia', 1),
                                                             (6, 'España', 0),
                                                             (6, 'Grecia', 0),
                                                             (6, 'Portugal', 0);
-- Historia & Artes (id: 4)

INSERT INTO respuestas (id_pregunta, texto, es_correcta) VALUES
                                                             (7, 'Vincent van Gogh', 1),
                                                             (7, 'Pablo Picasso', 0),
                                                             (7, 'Claude Monet', 0),
                                                             (7, 'Salvador Dalí', 0),
                                                             (8, '1969', 1),
                                                             (8, '1955', 0),
                                                             (8, '1972', 0),
                                                             (8, '1961', 0);
-- Deportes (id: 5)
INSERT INTO respuestas (id_pregunta, texto, es_correcta) VALUES
                                                             (9, '11', 1),
                                                             (9, '10', 0),
                                                             (9, '12', 0),
                                                             (9, '9', 0),
                                                             (10, 'Argentina', 1),
                                                             (10, 'Francia', 0),
                                                             (10, 'Croacia', 0),
                                                             (10, 'Brasil', 0);