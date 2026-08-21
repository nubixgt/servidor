<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

/**
 * Queries de agregación para el Asistente de WhatsApp: a diferencia de
 * ProgresoRepository (que expone filas crudas por lección/curso, usadas
 * por la app para llevar su propio cálculo), aquí se resuelve directamente
 * "% de avance por curso" y "próxima lección pendiente", que es lo que el
 * bot necesita mostrar como texto.
 */
class AsistenteRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @return array<int, array{id:int, titulo:string, total_lecciones:int, completadas:int, aprobado:bool}>
     */
    public function obtenerCursosConProgreso(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                c.id,
                c.titulo,
                COUNT(DISTINCT l.id) AS total_lecciones,
                COUNT(DISTINCT CASE WHEN pl.completada = 1 THEN pl.leccion_id END) AS completadas,
                COALESCE(pc.aprobado, 0) AS aprobado
             FROM cursos c
             LEFT JOIN lecciones l ON l.curso_id = c.id
             LEFT JOIN progreso_lecciones pl ON pl.leccion_id = l.id AND pl.usuario_id = :usuario_id1
             LEFT JOIN progreso_cursos pc ON pc.curso_id = c.id AND pc.usuario_id = :usuario_id2
             GROUP BY c.id, c.titulo, pc.aprobado
             ORDER BY c.id"
        );
        $stmt->execute(['usuario_id1' => $usuarioId, 'usuario_id2' => $usuarioId]);

        return array_map(
            fn($row) => [
                'id' => (int) $row['id'],
                'titulo' => $row['titulo'],
                'total_lecciones' => (int) $row['total_lecciones'],
                'completadas' => (int) $row['completadas'],
                'aprobado' => (bool) $row['aprobado'],
            ],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function primeraLeccionPendiente(int $usuarioId, int $cursoId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT l.titulo
             FROM lecciones l
             LEFT JOIN progreso_lecciones pl ON pl.leccion_id = l.id AND pl.usuario_id = :usuario_id
             WHERE l.curso_id = :curso_id AND (pl.completada IS NULL OR pl.completada = 0)
             ORDER BY l.orden ASC
             LIMIT 1"
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
        $titulo = $stmt->fetchColumn();

        return $titulo !== false ? $titulo : null;
    }

    public function crearConsulta(int $usuarioId, string $tipo, ?string $contenido, ?string $mensaje): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO consultas_tecnicas (usuario_id, tipo, contenido, mensaje)
             VALUES (:usuario_id, :tipo, :contenido, :mensaje)"
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'contenido' => $contenido,
            'mensaje' => $mensaje,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
