<?php
namespace App\Services;

use App\Repositories\CatalogoRepository;

class CatalogoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new CatalogoRepository();
    }

    public function catalogos(): array
    {
        $out = $this->repository->todos();
        foreach ($out['tipos_resolucion'] as &$t) {
            $t['activo'] = (bool) $t['activo'];
        }
        unset($t);
        $out['reglas'] = [];
        foreach (FiltroService::REGLAS as $clave => [, $titulo, $prioridad]) {
            $out['reglas'][] = ['clave' => $clave, 'titulo' => $titulo, 'prioridad' => $prioridad];
        }
        return $out;
    }

    /** Indicadores que no salen del cubo: posibles personas duplicadas y último reporte de importación. */
    public function calidadExtra(): array
    {
        return [
            'personas_posible_duplicado' => $this->repository->personasPosibleDuplicado(),
            'ultima_importacion' => $this->repository->ultimaImportacion(),
        ];
    }
}
