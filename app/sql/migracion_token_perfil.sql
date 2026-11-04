-- Ejecutar una sola vez sobre una base existente creada antes de token_perfil.
-- Completa un token para cada usuario sin eliminar ni reemplazar sus datos.
USE PrograWebII;

ALTER TABLE usuarios
    ADD COLUMN token_perfil CHAR(64) NULL AFTER nombre_usuario;

UPDATE usuarios
SET token_perfil = SHA2(CONCAT(id, UUID(), RAND()), 256)
WHERE token_perfil IS NULL OR token_perfil = '';

ALTER TABLE usuarios
    MODIFY token_perfil CHAR(64) NOT NULL,
    ADD UNIQUE INDEX uq_usuarios_token_perfil (token_perfil);
