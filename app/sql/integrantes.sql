
CREATE DATABASE IF NOT EXISTS PrograWebII CHARACTER SET utf8mb4;
USE PrograWebII;

CREATE TABLE integrantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL ,
    apellido VARCHAR(50) NOT NULL,
    dni VARCHAR(32) NOT NULL
);

INSERT INTO integrantes (nombre, apellido, dni) VALUES
('Micaela', 'Chaparro', 40891174),
('Sofia', 'Gandulfo', 46024938),
('Micaela', 'Mazza', 38662441),
('Julian', 'Susik', 44816527);


