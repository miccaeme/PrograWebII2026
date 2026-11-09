CREATE TABLE IF NOT EXISTS reportes_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta INT NOT NULL,
    id_usuario  INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resuelto TINYINT NOT NULL DEFAULT 0,
    UNIQUE (id_pregunta, id_usuario),
    motivo VARCHAR(100) NOT NULL DEFAULT ''
    );