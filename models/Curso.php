<?php
declare(strict_types=1);

/** Catálogo de cursos en línea (tabla cursos). url_externa NULL = curso dentro de la plataforma. */
class Curso
{
    public static function catalogo(): array
    {
        return db_all('SELECT id, titulo, categoria, duracion_horas, nivel, con_certificado, url_externa FROM cursos WHERE activo = 1 ORDER BY id');
    }
}
