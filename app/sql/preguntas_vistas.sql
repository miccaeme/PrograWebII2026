CREATE TABLE IF NOT EXISTS preguntas_vistas (
    id_usuario  INT NOT NULL,
    id_pregunta INT NOT NULL,
    fecha_vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario, id_pregunta)
    );