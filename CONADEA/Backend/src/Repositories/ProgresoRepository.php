<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\ProgresoLeccion;
use App\Entities\ProgresoCurso;
use PDO;

class ProgresoRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @return array{lecciones: ProgresoLeccion[], cursos: ProgresoCurso[]}
     */
    public function obtenerTodoDeUsuario(int $usuarioId): array
    {
        $stmtLecciones = $this->pdo->prepare(
            "SELECT pl.leccion_id, l.curso_id, pl.completada, pl.segundos_video
             FROM progreso_lecciones pl
             INNER JOIN lecciones l ON l.id = pl.leccion_id
             WHERE pl.usuario_id = :usuario_id"
        );
        $stmtLecciones->execute(['usuario_id' => $usuarioId]);
        $lecciones = array_map(
            fn($row) => new ProgresoLeccion(
                $usuarioId,
                (int) $row['leccion_id'],
                (int) $row['curso_id'],
                (bool) $row['completada'],
                (int) $row['segundos_video']
            ),
            $stmtLecciones->fetchAll(PDO::FETCH_ASSOC)
        );

        $stmtCursos = $this->pdo->prepare(
            "SELECT curso_id, nota, aprobado, fecha_aprobado
             FROM progreso_cursos WHERE usuario_id = :usuario_id"
        );
        $stmtCursos->execute(['usuario_id' => $usuarioId]);
        $cursos = array_map(
            fn($row) => new ProgresoCurso(
                $usuarioId,
                (int) $row['curso_id'],
                $row['nota'] !== null ? (int) $row['nota'] : null,
                (bool) $row['aprobado'],
                $row['fecha_aprobado']
            ),
            $stmtCursos->fetchAll(PDO::FETCH_ASSOC)
        );

        return ['lecciones' => $lecciones, 'cursos' => $cursos];
    }

    public function obtenerLeccion(int $usuarioId, int $leccionId): ?ProgresoLeccion
    {
        $stmt = $this->pdo->prepare(
            "SELECT pl.leccion_id, l.curso_id, pl.completada, pl.segundos_video
             FROM progreso_lecciones pl
             INNER JOIN lecciones l ON l.id = pl.leccion_id
             WHERE pl.usuario_id = :usuario_id AND pl.leccion_id = :leccion_id
             LIMIT 1"
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'leccion_id' => $leccionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new ProgresoLeccion(
            $usuarioId,
            (int) $row['leccion_id'],
            (int) $row['curso_id'],
            (bool) $row['completada'],
            (int) $row['segundos_video']
        );
    }

    public function obtenerCurso(int $usuarioId, int $cursoId): ?ProgresoCurso
    {
        $stmt = $this->pdo->prepare(
            "SELECT curso_id, nota, aprobado, fecha_aprobado
             FROM progreso_cursos WHERE usuario_id = :usuario_id AND curso_id = :curso_id LIMIT 1"
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new ProgresoCurso(
            $usuarioId,
            (int) $row['curso_id'],
            $row['nota'] !== null ? (int) $row['nota'] : null,
            (bool) $row['aprobado'],
            $row['fecha_aprobado']
        );
    }

    public function upsertLeccion(ProgresoLeccion $p): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO progreso_lecciones (usuario_id, leccion_id, completada, segundos_video)
             VALUES (:usuario_id, :leccion_id, :completada, :segundos_video)
             ON DUPLICATE KEY UPDATE completada = :completada2, segundos_video = :segundos_video2"
        );
        $stmt->execute([
            'usuario_id' => $p->usuarioId,
            'leccion_id' => $p->leccionId,
            'completada' => $p->completada ? 1 : 0,
            'segundos_video' => $p->segundosVideo,
            'completada2' => $p->completada ? 1 : 0,
            'segundos_video2' => $p->segundosVideo,
        ]);
    }

    public function upsertCurso(ProgresoCurso $p): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO progreso_cursos (usuario_id, curso_id, nota, aprobado, fecha_aprobado)
             VALUES (:usuario_id, :curso_id, :nota, :aprobado, :fecha_aprobado)
             ON DUPLICATE KEY UPDATE nota = :nota2, aprobado = :aprobado2, fecha_aprobado = :fecha_aprobado2"
        );
        $stmt->execute([
            'usuario_id' => $p->usuarioId,
            'curso_id' => $p->cursoId,
            'nota' => $p->nota,
            'aprobado' => $p->aprobado ? 1 : 0,
            'fecha_aprobado' => $p->fechaAprobado,
            'nota2' => $p->nota,
            'aprobado2' => $p->aprobado ? 1 : 0,
            'fecha_aprobado2' => $p->fechaAprobado,
        ]);
    }

    public function leccionExiste(int $leccionId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM lecciones WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $leccionId]);
        return (bool) $stmt->fetchColumn();
    }

    public function cursoIdDeLeccion(int $leccionId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT curso_id FROM lecciones WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $leccionId]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? (int) $valor : null;
    }

    public function cursoExiste(int $cursoId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM cursos WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $cursoId]);
        return (bool) $stmt->fetchColumn();
    }
}
