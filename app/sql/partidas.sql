CREATE TABLE IF NOT EXISTS partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_categoria INT NOT NULL,
    puntaje INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) NOT NULL DEFAULT 'en_curso',
    pregunta_actual_id INT NULL,
    pregunta_vence_en DATETIME(6) NULL,
    motivo_finalizacion VARCHAR(24) NULL,
    INDEX idx_usuario (id_usuario),
    INDEX idx_categoria (id_categoria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO partidas (id_usuario, id_categoria, puntaje, fecha_creacion, estado) VALUES
            (7, 1, 5, NOW(), 'finalizada'),
            (7, 3, 4, NOW() - INTERVAL 1 DAY, 'finalizada');













