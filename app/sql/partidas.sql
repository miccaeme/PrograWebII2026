
CREATE TABLE IF NOT EXISTS partidas (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        id_usuario INT NOT NULL,
                                        id_categoria INT NOT NULL,
                                        puntaje INT DEFAULT 0,
                                        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        INDEX idx_usuario (id_usuario),
    INDEX idx_categoria (id_categoria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO partidas (id_usuario, id_categoria, puntaje, fecha_creacion) VALUES
                                                                             (7, 1, 5, NOW()),
                                                                             (7, 3, 4, NOW() - INTERVAL 1 DAY);













