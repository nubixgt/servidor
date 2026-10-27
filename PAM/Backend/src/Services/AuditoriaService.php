<?php
namespace App\Services;

use App\Core\Request;
use App\Entities\Usuario;
use App\Repositories\AuditoriaRepository;

/**
 * Eventos de la bitácora que no salen de los triggers (login, ver DPI, exportar…).
 */
class AuditoriaService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new AuditoriaRepository();
    }

    public function registrar(?Usuario $u, string $accion, string $modulo, ?string $entidad = null, ?string $entidadId = null, ?array $detalle = null): void
    {
        $this->repository->registrar(
            $u?->id, $u?->usuario, $accion, $modulo, $entidad, $entidadId, $detalle,
            $u?->ip ?? Request::ip(), Request::userAgent(), $u?->requestId ?: Request::requestId()
        );
    }
}
