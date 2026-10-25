USE PrograWebII;

CREATE TABLE IF NOT EXISTS usuarios(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    anio_nacimiento SMALLINT NOT NULL,
    sexo ENUM('M', 'F', 'N') NOT NULL,
    pais VARCHAR(60) NOT NULL,
    ciudad VARCHAR(80) NOT NULL,
    latitud DECIMAL(10, 7) NOT NULL,
    longitud DECIMAL(10, 7) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    nombre_usuario VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) NULL,
    rol ENUM('jugador', 'editor', 'admin') NOT NULL DEFAULT 'jugador',
    activo TINYINT(1) NOT NULL DEFAULT 0,
    token_validacion VARCHAR(64) NULL UNIQUE,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    token_recuperacion VARCHAR(64) NULL UNIQUE,
    token_recuperacion_expira DATETIME NULL
);