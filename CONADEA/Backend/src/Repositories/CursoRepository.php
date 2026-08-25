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

    /**
     * @return array{0: int, 1: array<int, int>} [cursoId, [índice original en $curso->lecciones => leccionId]]
     */
    public function create(Curso $curso): array
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
            $stmtPregunta = $this->pdo->prepare(
                "INSERT INTO quiz_preguntas (leccion_id, orden, pregunta)
                 VALUES (:leccion_id, :orden, :pregunta)"
            );
            $stmtOpcion = $this->pdo->prepare(
                "INSERT INTO quiz_opciones (pregunta_id, orden, texto, es_correcta)
                 VALUES (:pregunta_id, :orden, :texto, :es_correcta)"
            );
            
            $leccionIds = [];
            foreach ($curso->lecciones as $i => $leccion) {
                $stmtLeccion->execute([
                    'curso_id' => $cursoId,
                    'orden' => $leccion->orden,
                    'titulo' => $leccion->titulo,
                    'contenido' => $leccion->contenido,
                ]);
                $leccionId = (int) $this->pdo->lastInsertId();
                $leccionIds[$i] = $leccionId;
                
                // Insert quiz questions for this lesson
                foreach ($leccion->quiz as $pregunta) {
                    $stmtPregunta->execute([
                        'leccion_id' => $leccionId,
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
            }

            $this->pdo->commit();
            return [$cursoId, $leccionIds];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * El listado no manda las lecciones completas (para no cargar de más),
     * pero sí cuántas tiene cada curso — la app lo necesita para calcular
     * bien el % de avance sin tener que pedir el curso completo.
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT c.id, c.icono, c.titulo, c.descripcion, c.imagen_path,
                    (SELECT COUNT(*) FROM lecciones l WHERE l.curso_id = c.id) AS total_lecciones
             FROM cursos c ORDER BY c.id DESC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn($row) => new Curso(
                (int) $row['id'],
                $row['icono'],
                $row['titulo'],
                $row['descripcion'],
                $row['imagen_path'],
                [],
                (int) $row['total_lecciones']
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

        return new Curso(
            (int) $row['id'],
            $row['icono'],
            $row['titulo'],
            $row['descripcion'],
            $row['imagen_path'],
            $lecciones
        );
    }

    public function actualizarImagen(int $id, string $imagenPath): void
    {
        $stmt = $this->pdo->prepare("UPDATE cursos SET imagen_path = :imagen_path WHERE id = :id");
        $stmt->execute(['imagen_path' => $imagenPath, 'id' => $id]);
    }

    public function actualizarVideoLeccion(int $leccionId, string $videoPath): void
    {
        $stmt = $this->pdo->prepare("UPDATE lecciones SET video_path = :video_path WHERE id = :id");
        $stmt->execute(['video_path' => $videoPath, 'id' => $leccionId]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM cursos WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    private function findLeccionesByCurso(int $cursoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, curso_id, orden, titulo, contenido, video_path
             FROM lecciones WHERE curso_id = :curso_id ORDER BY orden ASC"
        );
        $stmt->execute(['curso_id' => $cursoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            $quiz = $this->findQuizByLeccion((int) $row['id']);
            return new Leccion(
                (int) $row['id'],
                (int) $row['curso_id'],
                (int) $row['orden'],
                $row['titulo'],
                $row['contenido'],
                $row['video_path'],
                $quiz
            );
        }, $rows);
    }

    private function findQuizByLeccion(int $leccionId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, leccion_id, orden, pregunta
             FROM quiz_preguntas WHERE leccion_id = :leccion_id ORDER BY orden ASC"
        );
        $stmt->execute(['leccion_id' => $leccionId]);
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
                (int) $row['leccion_id'],
                (int) $row['orden'],
                $row['pregunta'],
                $opciones
            );
        }, $preguntas);
    }
}
