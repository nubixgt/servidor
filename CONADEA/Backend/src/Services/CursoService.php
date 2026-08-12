<?php
namespace App\Services;

use App\Repositories\CursoRepository;
use App\DTOs\CrearCursoDTO;
use App\Entities\Curso;
use App\Entities\Leccion;
use App\Entities\PreguntaQuiz;
use App\Entities\OpcionQuiz;
use App\Utils\UrlHelper;

class CursoService
{
    private const TAMANO_MAXIMO_BYTES = 5 * 1024 * 1024; // 5 MB
    private const EXTENSIONES_PERMITIDAS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const VIDEO_TAMANO_MAXIMO_BYTES = 300 * 1024 * 1024; // 300 MB
    private const VIDEO_EXTENSIONES_PERMITIDAS = [
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
    ];

    private $repository;

    public function __construct()
    {
        $this->repository = new CursoRepository();
    }

    /**
     * @param array|null $archivoImagen Entrada cruda de $_FILES['imagen']
     * @param array<int, array> $archivosVideo Entradas crudas de $_FILES['leccion_video_N'],
     *        indexadas por la posición de la lección en $dto->lecciones. El video es opcional
     *        por lección, así que este array puede traer solo algunos índices (o ninguno).
     */
    public function crear(CrearCursoDTO $dto, ?array $archivoImagen, array $archivosVideo = []): array
    {
        $this->validar($dto);
        $this->validarImagen($archivoImagen);
        foreach ($archivosVideo as $archivo) {
            $this->validarVideo($archivo);
        }

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

        // La imagen (y los videos de lección) se guardan después de insertar
        // porque el nombre de la carpeta depende del id autoincremental.
        $curso = new Curso(null, $dto->icono, $dto->titulo, $dto->descripcion, '', $lecciones, $quiz);
        [$id, $leccionIds] = $this->repository->create($curso);

        try {
            $imagenPath = $this->guardarImagen($id, $archivoImagen);
            $this->repository->actualizarImagen($id, $imagenPath);

            foreach ($archivosVideo as $i => $archivo) {
                if (!isset($leccionIds[$i])) {
                    continue;
                }
                $videoPath = $this->guardarVideoLeccion($id, $leccionIds[$i], $archivo);
                $this->repository->actualizarVideoLeccion($leccionIds[$i], $videoPath);
            }
        } catch (\Exception $e) {
            $this->repository->eliminar($id);
            throw new \Exception('No se pudo guardar la imagen o el video del curso. Intenta de nuevo.');
        }

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
                'imagen_url' => UrlHelper::toAbsolute($c->imagenPath),
                'total_lecciones' => $c->totalLecciones,
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

    private function validarImagen(?array $archivo): void
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new \Exception('Selecciona o toma una foto para el curso.');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Ocurrió un problema al subir la imagen. Intenta de nuevo.');
        }
        if ($archivo['size'] > self::TAMANO_MAXIMO_BYTES) {
            throw new \Exception('La imagen no puede pesar más de 5 MB.');
        }
    }

    /**
     * Valida (con getimagesize, no confiando en la extensión que mande el
     * cliente) que el archivo sea una imagen real y la guarda en
     * Backend/uploads/cursos/{id}/imagen.<ext>.
     */
    private function guardarImagen(int $cursoId, array $archivo): string
    {
        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false) {
            throw new \Exception('El archivo no es una imagen válida.');
        }

        $extension = self::EXTENSIONES_PERMITIDAS[$info['mime']] ?? null;
        if ($extension === null) {
            throw new \Exception('Formato de imagen no soportado. Usa JPG, PNG o WEBP.');
        }

        $directorio = __DIR__ . '/../../uploads/cursos/' . $cursoId;
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new \Exception('No se pudo crear la carpeta de subida.');
        }

        $rutaAbsoluta = $directorio . '/imagen.' . $extension;
        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            throw new \Exception('No se pudo guardar el archivo de imagen.');
        }

        return "uploads/cursos/{$cursoId}/imagen.{$extension}";
    }

    private function validarVideo(array $archivo): void
    {
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Ocurrió un problema al subir un video de lección. Intenta de nuevo.');
        }
        if ($archivo['size'] > self::VIDEO_TAMANO_MAXIMO_BYTES) {
            throw new \Exception('Cada video de lección no puede pesar más de 300 MB.');
        }
    }

    /**
     * No se puede validar un video con getimagesize; se confía en el MIME
     * que reporta PHP a partir del contenido real del archivo (fileinfo),
     * no en la extensión que mande el cliente. Se guarda en
     * Backend/uploads/cursos/{cursoId}/lecciones/{orden}/video.<ext>.
     */
    private function guardarVideoLeccion(int $cursoId, int $leccionId, array $archivo): string
    {
        $mime = @mime_content_type($archivo['tmp_name']);
        $extension = self::VIDEO_EXTENSIONES_PERMITIDAS[$mime] ?? null;
        if ($extension === null) {
            throw new \Exception('Formato de video no soportado. Usa MP4 o MOV.');
        }

        $directorio = __DIR__ . '/../../uploads/cursos/' . $cursoId . '/lecciones/' . $leccionId;
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new \Exception('No se pudo crear la carpeta de subida del video.');
        }

        $rutaAbsoluta = $directorio . '/video.' . $extension;
        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            throw new \Exception('No se pudo guardar el archivo de video.');
        }

        return "uploads/cursos/{$cursoId}/lecciones/{$leccionId}/video.{$extension}";
    }

    private function toArray(Curso $curso): array
    {
        return [
            'id' => $curso->id,
            'icono' => $curso->icono,
            'titulo' => $curso->titulo,
            'descripcion' => $curso->descripcion,
            'imagen_url' => UrlHelper::toAbsolute($curso->imagenPath),
            'total_lecciones' => count($curso->lecciones),
            'lecciones' => array_map(
                fn(Leccion $l) => [
                    'id' => $l->id,
                    'titulo' => $l->titulo,
                    'contenido' => $l->contenido,
                    'video_url' => $l->videoPath !== null ? UrlHelper::toAbsolute($l->videoPath) : null,
                ],
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
