<?php
namespace App\Services;

use App\DTOs\DanoCultivoDTO;
use App\Entities\DanoCultivo;
use App\Repositories\DanoCultivoRepository;
use App\Utils\Periodo;

class DanoCultivoService
{
    private const MAX_ASOCIADOS = 20;
    private const MAX_FOTOS = 10;
    private const MAX_SIZE = 12 * 1024 * 1024; // 12MB
    private const ALLOWED_MIME = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];

    private DanoCultivoRepository $repository;
    private string $uploadsRoot;

    public function __construct()
    {
        $this->repository = new DanoCultivoRepository();
        $this->uploadsRoot = dirname(__DIR__, 2) . '/uploads';
    }

    /** @return DanoCultivo[] */
    public function listar(array $query, array $user = []): array
    {
        $criteria = [
            'q' => $query['q'] ?? null,
            'desde' => $query['desde'] ?? null,
            'hasta' => $query['hasta'] ?? null,
        ];
        // El técnico solo ve sus propios registros; admin y supervisor ven todos.
        if (($user['role'] ?? '') === 'tecnico') {
            $criteria['usuarioId'] = (int)($user['sub'] ?? 0);
        }
        return $this->repository->findByFilters($criteria);
    }

    public function crear(DanoCultivoDTO $dto, array $user = []): DanoCultivo
    {
        $f = $dto->fields;
        if ($f['fecha'] === '' || $f['responsable'] === '' || $f['lote'] === '' || $f['cultivo'] === '' || $f['causa'] === '') {
            throw new \Exception('Fecha, productor, lote, cultivo y causa son obligatorios.', 400);
        }
        if ($f['departamento'] === '' || $f['municipio'] === '' || $f['centroPoblado'] === '') {
            throw new \Exception('Departamento, municipio y centro poblado son obligatorios.', 400);
        }
        if ($f['agricultorRua'] && $f['codigoRua'] === '') {
            throw new \Exception('El código de RUA es obligatorio cuando el agricultor está en RUA.', 400);
        }
        if ($f['causa'] === 'Otra' && $f['causaOtra'] === '') {
            throw new \Exception('Especifica la causa del daño.', 400);
        }
        if ($f['gpsLat'] === null || $f['gpsLon'] === null) {
            throw new \Exception('La ubicación actual (GPS) es obligatoria.', 400);
        }
        if (count($dto->asociados) > self::MAX_ASOCIADOS) {
            throw new \Exception('Máximo ' . self::MAX_ASOCIADOS . ' cultivos asociados.', 400);
        }
        foreach ($dto->asociados as $a) {
            if ($a['cultivo'] === '') {
                throw new \Exception('Cada cultivo asociado necesita un cultivo.', 400);
            }
            foreach (['areaLote', 'areaAfectada'] as $k) {
                if ($a[$k] !== null && $a[$k] < 0) {
                    throw new \Exception('Los valores numéricos no pueden ser negativos.', 400);
                }
            }
        }
        $fecha = \DateTime::createFromFormat('Y-m-d', $f['fecha']);
        if (!$fecha || $fecha->format('Y-m-d') !== $f['fecha']) {
            throw new \Exception('Fecha inválida.', 400);
        }
        if ($f['areaAfectada'] === null || $f['areaAfectada'] < 0) {
            throw new \Exception('La superficie afectada es obligatoria y no puede ser negativa.', 400);
        }
        if ($f['danoPorc'] === null || $f['danoPorc'] < 0 || $f['danoPorc'] > 100) {
            throw new \Exception('El % de daño debe estar entre 0 y 100.', 400);
        }
        foreach (['areaLote', 'rendEsperado', 'perdidaEstimada'] as $k) {
            if ($f[$k] !== null && $f[$k] < 0) {
                throw new \Exception('Los valores numéricos no pueden ser negativos.', 400);
            }
        }
        if (!in_array($f['unidad'], ['t/ha', 'kg/ha'], true)) {
            throw new \Exception('Unidad de rendimiento inválida.', 400);
        }
        if (($f['gpsLat'] !== null && abs($f['gpsLat']) > 90) || ($f['gpsLon'] !== null && abs($f['gpsLon']) > 180)) {
            throw new \Exception('Coordenadas GPS inválidas.', 400);
        }
        $f = $this->normalizarAfectaciones($f);
        $id = $this->repository->create($f, $dto->asociados, isset($user['sub']) ? (int)$user['sub'] : null, $user['usuario'] ?? null);
        return $this->repository->findById($id);
    }

    /** Valida y completa los campos de Afectaciones 2026 (período, semana ISO, familias por tipo de agricultor). */
    private function normalizarAfectaciones(array $f): array
    {
        Periodo::fecha($f['fechaInforme'], 'La fecha de informe');
        Periodo::fecha($f['fechaSiembra'], 'La fecha de siembra');
        Periodo::rango($f['monitoreoInicio'], $f['monitoreoFin']);
        foreach (['famAfectadas' => 'Familias afectadas', 'famAutoconsumo' => 'Familias de autoconsumo', 'famIntermedia' => 'Familias de agricultura intermedia', 'famConsolidada' => 'Familias de agricultura consolidada'] as $k => $etiqueta) {
            Periodo::noNegativo($f[$k], $etiqueta);
            $f[$k] = Periodo::entero($f[$k]);
        }
        $desglose = [$f['famAutoconsumo'], $f['famIntermedia'], $f['famConsolidada']];
        if (array_filter($desglose, fn($v) => $v !== null)) {
            if ($f['famAfectadas'] === null) {
                throw new \Exception('Indica las familias afectadas para usar el desglose por tipo de agricultor.', 400);
            }
            if (array_sum($desglose) !== $f['famAfectadas']) {
                throw new \Exception('Autoconsumo + Intermedia + Consolidada debe sumar las familias afectadas.', 400);
            }
        }
        [$f['semana'], $f['anioSemana']] = Periodo::semana($f['fechaInforme'] ?? $f['fecha']);
        return $f;
    }

    public function eliminar(int $id): void
    {
        $dano = $this->obtener($id);
        $this->repository->delete($dano->id);
        $this->borrarCarpeta($dano->id);
    }

    public function subirFotos(int $id, array $files, array $user = []): array
    {
        $dano = $this->obtener($id);
        if (($user['role'] ?? '') === 'tecnico' && $dano->usuarioId !== (int)($user['sub'] ?? 0)) {
            throw new \Exception('No puedes modificar registros de otro usuario.', 403);
        }
        $normalized = $this->normalizeFilesArray($files);
        if (!$normalized) {
            throw new \Exception('No se recibió ninguna foto.', 400);
        }
        if ($this->repository->countFotos($dano->id) + count($normalized) > self::MAX_FOTOS) {
            throw new \Exception('Máximo ' . self::MAX_FOTOS . ' fotos por registro.', 400);
        }

        $dir = $this->uploadsRoot . '/danos-cultivos/' . $dano->id . '/fotos';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $creadas = [];
        foreach ($normalized as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }
            if ($file['size'] > self::MAX_SIZE) {
                throw new \Exception('Cada foto debe pesar máximo 12MB.', 400);
            }
            $mime = mime_content_type($file['tmp_name']);
            if (!isset(self::ALLOWED_MIME[$mime])) {
                throw new \Exception('Solo se permiten imágenes JPG, PNG o WEBP.', 400);
            }
            $filename = sprintf('%d-%s%s', (int)(microtime(true) * 1000), bin2hex(random_bytes(6)), self::ALLOWED_MIME[$mime]);
            if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
                continue;
            }
            $creadas[] = $this->repository->addFoto(
                $dano->id,
                $filename,
                mb_substr((string)$file['name'], 0, 255),
                ''
            );
        }
        return $creadas;
    }

    private function obtener(int $id): DanoCultivo
    {
        $dano = $this->repository->findById($id);
        if (!$dano) {
            throw new \Exception('Registro no encontrado.', 404);
        }
        return $dano;
    }

    private function borrarCarpeta(int $id): void
    {
        $dir = $this->uploadsRoot . '/danos-cultivos/' . $id;
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    /** Normaliza $_FILES['fotos'] (array de campos paralelos) a una lista de archivos individuales. */
    private function normalizeFilesArray(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return $files['error'] === UPLOAD_ERR_NO_FILE ? [] : [$files];
        }
        $result = [];
        foreach (array_keys($files['name']) as $i) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $result[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }
        return $result;
    }
}
