<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\NovedadDTO;
use App\Repositories\NovedadRepository;
use App\Utils\FileUpload;

class NovedadService
{
    private const PORTADA_MAX_BYTES = 8 * 1024 * 1024;
    private const PDF_MAX_BYTES = 10 * 1024 * 1024;

    private NovedadRepository $repository;

    public function __construct()
    {
        $this->repository = new NovedadRepository();
    }

    public function listPublicas(?string $categoria = null): array
    {
        return array_map(
            [$this, 'present'],
            $this->repository->findAll(['soloPublicadas' => true, 'categoria' => $categoria])
        );
    }

    public function listAdmin(array $filters = []): array
    {
        return array_map([$this, 'present'], $this->repository->findAll($filters));
    }

    public function get(int $id): array
    {
        $row = $this->repository->findById($id);
        if (!$row) {
            throw HttpException::notFound('Novedad no encontrada');
        }
        return $this->present($row);
    }

    public function create(NovedadDTO $dto, ?int $autorId): array
    {
        $this->validate($dto);
        $id = $this->repository->create((array) $dto + ['autor_id' => $autorId]);
        return $this->get($id);
    }

    public function update(int $id, NovedadDTO $dto): array
    {
        $current = $this->repository->findById($id);
        if (!$current) {
            throw HttpException::notFound('Novedad no encontrada');
        }
        $this->validate($dto);
        $nowPublished = $dto->estado === 'publicado';
        $this->repository->update($id, (array) $dto, $nowPublished);
        return $this->get($id);
    }

    public function delete(int $id): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Novedad no encontrada');
        }
        $this->repository->delete($id);
        return ['modo' => 'eliminada'];
    }

    public function uploadPortada(int $id, array $file): array
    {
        $this->assertExists($id);
        $ruta = FileUpload::save($file, 'novedades', $id, 'portada', 'image', self::PORTADA_MAX_BYTES);
        $this->repository->setArchivo($id, 'portada_ruta', $ruta);
        return $this->get($id);
    }

    public function uploadPdf(int $id, array $file): array
    {
        $this->assertExists($id);
        $ruta = FileUpload::save($file, 'novedades', $id, 'doc', 'pdf', self::PDF_MAX_BYTES);
        $this->repository->setArchivo($id, 'pdf_ruta', $ruta);
        return $this->get($id);
    }

    private function assertExists(int $id): void
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Novedad no encontrada');
        }
    }

    private function validate(NovedadDTO $dto): void
    {
        if ($dto->titulo === '') {
            throw HttpException::validation('El título es obligatorio');
        }
    }

    private function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'titulo' => $row['titulo'],
            'categoria' => $row['categoria'],
            'cuerpo' => $row['cuerpo'],
            'portada_ruta' => $row['portada_ruta'],
            'pdf_ruta' => $row['pdf_ruta'],
            'fijado' => (bool) $row['fijado'],
            'estado' => $row['estado'],
            'fecha_emision' => $row['fecha_emision'],
            'publicado_en' => $row['publicado_en'],
            'autor_id' => $row['autor_id'] !== null ? (int) $row['autor_id'] : null,
            'autor_nombre' => $row['autor_nombre'] ?? null,
        ];
    }
}
