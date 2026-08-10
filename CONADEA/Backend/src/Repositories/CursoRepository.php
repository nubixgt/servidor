<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Curso;
use App\Entities\Leccion;
use App\Entities\PreguntaQuiz;
use App\Entities\OpcionQuiz;
use PDO;

class CursoRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(Curso $curso): int
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO cursos (icono, titulo, descripcion, imagen_path)
                 VALUES (:icono, :titulo, :descripcion, :imagen_path)"
            );
            $stmt->execute([
                'icono' => $curso->icono,
                'titulo' => $curso->titulo,
                'descripcion' => $curso->descripcion,
                'imagen_path' => $curso->imagenPath,
            ]);
            $cursoId = (int) $this->pdo->lastInsertId();

            $stmtLeccion = $this->pdo->prepare(
                "INSERT INTO lecciones (curso_id, orden, titulo, contenido)
                 VALUES (:curso_id, :orden, :titulo, :contenido)"
            );
            foreach ($curso->lecciones as $leccion) {
                $stmtLeccion->execute([
                    'curso_id' => $cursoId,
                    'orden' => $leccion->orden,
                    'titulo' => $leccion->titulo,
                    'contenido' => $leccion->contenido,
                ]);
            }

            $stmtPregunta = $this->pdo->prepare(
                "INSERT INTO quiz_preguntas (curso_id, orden, pregunta)
                 VALUES (:curso_id, :orden, :pregunta)"
            );
            $stmtOpcion = $this->pdo->prepare(
                "INSERT INTO quiz_opciones (pregunta_id, orden, texto, es_correcta)
                 VALUES (:pregunta_id, :orden, :texto, :es_correcta)"
            );
            foreach ($curso->quiz as $pregunta) {
                $stmtPregunta->execute([
                    'curso_id' => $cursoId,
                    'orden' => $pregunta->orden,
                    'pregunta' => $pregunta->pregunta,
                ]);
                $preguntaId = (int) $this->pdo->lastInsertId();

                foreach ($pregunta->opciones as $opcion) {
                    $stmtOpcion->execute([
                        'pregunta_id' => $preguntaId,
                        'orden' => $opcion->orden,
                        'texto' => $opcion->texto,
                        'es_correcta' => $opcion->esCorrecta ? 1 : 0,
                    ]);
                }
            }

            $this->pdo->commit();
            return $cursoId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, icono, titulo, descripcion, imagen_path FROM cursos ORDER BY id DESC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn($row) => new Curso(
                (int) $row['id'],
                $row['icono'],
                $row['titulo'],
                $row['descripcion'],
                $row['imagen_path']
            ),
            $rows
        );
    }

    public function findById(int $id): ?Curso
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, icono, titulo, descripcion, imagen_path FROM cursos WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $lecciones = $this->findLeccionesByCurso($id);
        $quiz = $this->findQuizByCurso($id);

        return new Curso(
            (int) $row['id'],
            $row['icono'],
            $row['titulo'],
            $row['descripcion'],
            $row['imagen_path'],
            $lecciones,
            $quiz
        );
    }

    public function actualizarImagen(int $id, string $imagenPath): void
    {
        $stmt = $this->pdo->prepare("UPDATE cursos SET imagen_path = :imagen_path WHERE id = :id");
        $stmt->execute(['imagen_path' => $imagenPath, 'id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM cursos WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    private function findLeccionesByCurso(int $cursoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, curso_id, orden, titulo, contenido
             FROM lecciones WHERE curso_id = :curso_id ORDER BY orden ASC"
        );
        $stmt->execute(['curso_id' => $cursoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn($row) => new Leccion(
                (int) $row['id'],
                (int) $row['curso_id'],
                (int) $row['orden'],
                $row['titulo'],
                $row['contenido']
            ),
            $rows
        );
    }

    private function findQuizByCurso(int $cursoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, curso_id, orden, pregunta
             FROM quiz_preguntas WHERE curso_id = :curso_id ORDER BY orden ASC"
        );
        $stmt->execute(['curso_id' => $cursoId]);
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtOpciones = $this->pdo->prepare(
            "SELECT id, pregunta_id, orden, texto, es_correcta
             FROM quiz_opciones WHERE pregunta_id = :pregunta_id ORDER BY orden ASC"
        );

        return array_map(function ($row) use ($stmtOpciones) {
            $stmtOpciones->execute(['pregunta_id' => $row['id']]);
            $opcionesRows = $stmtOpciones->fetchAll(PDO::FETCH_ASSOC);

            $opciones = array_map(
                fn($o) => new OpcionQuiz(
                    (int) $o['id'],
                    (int) $o['pregunta_id'],
                    (int) $o['orden'],
                    $o['texto'],
                    (bool) $o['es_correcta']
                ),
                $opcionesRows
            );

            return new PreguntaQuiz(
                (int) $row['id'],
                (int) $row['curso_id'],
                (int) $row['orden'],
                $row['pregunta'],
                $opciones
            );
        }, $preguntas);
    }
}
