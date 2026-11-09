
ALTER TABLE partidas
    ADD COLUMN pregunta_actual_id INT NULL,
    ADD COLUMN pregunta_vence_en DATETIME(6) NULL,
    ADD COLUMN motivo_finalizacion VARCHAR(24) NULL;

