USE PrograWebII;

ALTER TABLE usuarios
    ADD COLUMN preguntas_respondidas INT NOT NULL DEFAULT 0,
    ADD COLUMN preguntas_acertadas INT NOT NULL DEFAULT 0;