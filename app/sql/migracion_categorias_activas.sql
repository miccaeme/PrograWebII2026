-- Ejecutar una sola vez en una base creada antes del ABM de categorías.
USE PrograWebII;

ALTER TABLE categorias
    ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 1;

-- Algunas bases anteriores no tenían una restricción única para nombre.
SET @crear_indice_categorias = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE categorias ADD UNIQUE INDEX uq_categorias_nombre (nombre)',
        'DO 0')
    FROM (
        SELECT INDEX_NAME FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categorias' AND NON_UNIQUE = 0
        GROUP BY INDEX_NAME
        HAVING COUNT(*) = 1 AND MAX(COLUMN_NAME) = 'nombre' AND MAX(SUB_PART) IS NULL
    ) AS indices_nombre
);
PREPARE crear_indice_categorias FROM @crear_indice_categorias;
EXECUTE crear_indice_categorias;
DEALLOCATE PREPARE crear_indice_categorias;
