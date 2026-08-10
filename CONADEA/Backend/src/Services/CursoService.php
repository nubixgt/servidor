<?php
namespace App\Services;

use App\Repositories\CursoRepository;
use App\DTOs\CrearCursoDTO;
use App\Entities\Curso;
use App\Entities\Leccion;
use App\Entities\PreguntaQuiz;
use App\Entities\OpcionQuiz;

class CursoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new CursoRepository();
    }

    public function crear(CrearCursoDTO $dto): array
    {
        $this->validar($dto);

        $lecciones = [];
        foreach (array_values($dto->lecciones) as $i => $leccion) {
            $lecciones[] = new Leccion(null, 0, $i, trim($leccion['titulo'] ?? ''), trim($leccion['contenido'] ?? ''));
        }

        $quiz = [];
        foreach (array_values($dto->quiz) as $i => $pregunta) {
            $opciones = [];
            foreach (array_values($pregunta['opciones'] ?? []) as $j => $opcion) {
                $opciones[] = new OpcionQuiz(
                    null,
                    0,
                    $j,
                    trim($opcion['texto'] ?? ''),
                    (bool) ($opcion['es_correcta'] ?? false)
                );
            }
            $quiz[] = new PreguntaQuiz(null, 0, $i, trim($pregunta['pregunta'] ?? ''), $opciones);
        }

        $curso = new Curso(null, $dto->icono, $dto->titulo, $dto->descripcion, $dto->imagenUrl, $lecciones, $quiz);
        $id = $this->repository->create($curso);

        return $this->toArray($this->repository->findById($id));
    }

    public function listar(): array
    {
        return array_map(
            fn(Curso $c) => [
                'id' => $c->id,
                'icono' => $c->icono,
                'titulo' => $c->titulo,
                'descripcion' => $c->descripcion,
                'imagen_url' => $c->imagenUrl,
            ],
            $this->repository->findAll()
        );
    }

    public function obtener(int $id): array
    {
        $curso = $this->repository->findById($id);
        if (!$curso) {
            throw new \Exception('Curso no encontrado.');
        }
        return $this->toArray($curso);
    }

    private function validar(CrearCursoDTO $dto): void
    {
        if ($dto->icono === '') {
            throw new \Exception('El ícono del curso es obligatorio.');
        }
        if (mb_strlen($dto->titulo) < 3) {
            throw new \Exception('El título del curso es obligatorio.');
        }
        if ($dto->descripcion === '') {
            throw new \Exception('La descripción del curso es obligatoria.');
        }
        if (!preg_match('#^https?://#i', $dto->imagenUrl)) {
            throw new \Exception('La imagen debe ser una URL válida (http:// o https://).');
        }
        if (count($dto->lecciones) < 1) {
            throw new \Exception('Agrega al menos una lección.');
        }
        foreach ($dto->lecciones as $leccion) {
            if (trim($leccion['titulo'] ?? '') === '' || trim($leccion['contenido'] ?? '') === '') {
                throw new \Exception('Cada lección necesita título y contenido.');
            }
        }
        if (count($dto->quiz) < 1) {
            throw new \Exception('Agrega al menos una pregunta al quiz.');
        }
        foreach ($dto->quiz as $pregunta) {
            if (trim($pregunta['pregunta'] ?? '') === '') {
                throw new \Exception('Cada pregunta del quiz necesita texto.');
            }
            $opciones = $pregunta['opciones'] ?? [];
            if (count($opciones) < 2) {
                throw new \Exception('Cada pregunta necesita al menos 2 opciones.');
            }
            $correctas = 0;
            foreach ($opciones as $opcion) {
                if (trim($opcion['texto'] ?? '') === '') {
                    throw new \Exception('Todas las opciones necesitan texto.');
                }
                if (!empty($opcion['es_correcta'])) {
                    $correctas++;
                }
            }
            if ($correctas !== 1) {
                throw new \Exception('Cada pregunta debe tener exactamente una opción marcada como correcta.');
            }
        }
    }

    private function toArray(Curso $curso): array
    {
        return [
            'id' => $curso->id,
            'icono' => $curso->icono,
            'titulo' => $curso->titulo,
            'descripcion' => $curso->descripcion,
            'imagen_url' => $curso->imagenUrl,
            'lecciones' => array_map(
                fn(Leccion $l) => ['titulo' => $l->titulo, 'contenido' => $l->contenido],
                $curso->lecciones
            ),
            'quiz' => array_map(
                fn(PreguntaQuiz $p) => [
                    'pregunta' => $p->pregunta,
                    'opciones' => array_map(
                        fn(OpcionQuiz $o) => ['texto' => $o->texto, 'es_correcta' => $o->esCorrecta],
                        $p->opciones
                    ),
                ],
                $curso->quiz
            ),
        ];
    }
}
