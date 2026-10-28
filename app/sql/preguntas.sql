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
    FOREIGN KEY (id_categoria) REFERENCES categorias(id),
    FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS respuestas(
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta INT NOT NULL,
    texto VARCHAR(150) NOT NULL,
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_pregunta) REFERENCES preguntas(id) ON DELETE CASCADE
);