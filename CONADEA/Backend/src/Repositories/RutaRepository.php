<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\RutaAprendizaje;
use PDO;

class RutaRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(RutaAprendizaje $ruta): int
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO rutas_aprendizaje (icono, titulo, descripcion, color)
                 VALUES (:icono, :titulo, :descripcion, :color)"
            );
            $stmt->execute([
                'icono' => $ruta->icono,
                'titulo' => $ruta->titulo,
                'descripcion' => $ruta->descripcion,
                'color' => $ruta->color,
            ]);
            $id = (int) $this->pdo->lastInsertId();

            $this->reemplazarCursos($id, $ruta->cursoIds);

            $this->pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, RutaAprendizaje $ruta): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE rutas_aprendizaje
                 SET icono = :icono, titulo = :titulo, descripcion = :descripcion, color = :color
                 WHERE id = :id"
            );
            $stmt->execute([
                'icono' => $ruta->icono,
                'titulo' => $ruta->titulo,
                'descripcion' => $ruta->descripcion,
                'color' => $ruta->color,
                'id' => $id,
            ]);

            $this->pdo->prepare("DELETE FROM ruta_cursos WHERE ruta_id = :ruta_id")->execute(['ruta_id' => $id]);
            $this->reemplazarCursos($id, $ruta->cursoIds);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM rutas_aprendizaje WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function existsById(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM rutas_aprendizaje WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, icono, titulo, descripcion, color FROM rutas_aprendizaje ORDER BY id ASC");
        return array_map(fn($row) => $this->hidratar($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findById(int $id): ?RutaAprendizaje
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, icono, titulo, descripcion, color FROM rutas_aprendizaje WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hidratar($row) : null;
    }

    private function reemplazarCursos(int $rutaId, array $cursoIds): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ruta_cursos (ruta_id, curso_id, orden) VALUES (:ruta_id, :curso_id, :orden)"
        );
        foreach (array_values($cursoIds) as $i => $cursoId) {
            $stmt->execute(['ruta_id' => $rutaId, 'curso_id' => $cursoId, 'orden' => $i]);
        }
    }

    private function hidratar(array $row): RutaAprendizaje
    {
        $stmtCursos = $this->pdo->prepare(
            "SELECT curso_id FROM ruta_cursos WHERE ruta_id = :ruta_id ORDER BY orden ASC"
        );
        $stmtCursos->execute(['ruta_id' => $row['id']]);
        $cursoIds = array_map('intval', $stmtCursos->fetchAll(PDO::FETCH_COLUMN));

        return new RutaAprendizaje(
            (int) $row['id'],
            $row['icono'],
            $row['titulo'],
            $row['descripcion'],
            $row['color'],
            $cursoIds
        );
    }
}
