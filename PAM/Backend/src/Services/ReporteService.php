<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\ExportacionDTO;
use App\Entities\Usuario;
use App\Repositories\ReporteRepository;
use App\Utils\Security;
use App\Utils\Validator;

/**
 * Reportes y exportación controlada (CSV). Toda exportación queda en la bitácora.
 */
class ReporteService
{
    /** id => [título, descripción, requiere datos personales] */
    private const CATALOGO = [
        'resumen_departamental' => ['Resumen departamental', 'Indicadores por departamento', false],
        'resumen_municipal' => ['Resumen municipal', 'Indicadores por municipio', false],
        'estados' => ['Estados actuales', 'Expedientes por estado y departamento', false],
        'edad' => ['Distribución por edad', 'Rangos de edad por estado', false],
        'antiguedad' => ['Antigüedad de expedientes', 'Pendientes por rango de días y departamento', false],
        'resoluciones' => ['Resoluciones', 'Última resolución por familia y departamento', false],
        'serie_mensual' => ['Movimientos por mes', 'Solicitudes, visitas, resoluciones, ingresos, salidas, suspensiones y fallecimientos', false],
        'calidad' => ['Calidad de datos', 'Hallazgos por regla y departamento', false],
        'listado' => ['Listado de expedientes', 'Detalle con datos personales según los filtros (DPI enmascarado salvo permiso)', true],
    ];

    private $repository;
    private $filtros;
    private $auditoria;

    public function __construct()
    {
        $this->repository = new ReporteRepository();
        $this->filtros = new FiltroService();
        $this->auditoria = new AuditoriaService();
    }

    public function catalogo(Usuario $u): array
    {
        $out = [];
        foreach (self::CATALOGO as $id => [$titulo, $descripcion, $personal]) {
            $out[] = ['id' => $id, 'titulo' => $titulo, 'descripcion' => $descripcion, 'datos_personales' => $personal,
                'permitido' => !$personal || $u->puede('reportes.exportar')];
        }
        return $out;
    }

    /**
     * Valida, audita y devuelve lo necesario para escribir el archivo.
     * @return array{nombre: string, marca: string, cols: array, filas: iterable}
     */
    public function exportar(Usuario $u, ExportacionDTO $dto): array
    {
        if (!isset(self::CATALOGO[$dto->tipo])) {
            throw new HttpException('Reporte desconocido', 400);
        }
        if ($dto->formato !== 'csv') {
            throw new HttpException('Formato no disponible: la exportación es en CSV (se abre directamente en Excel)', 400);
        }
        $motivo = Validator::texto($dto->motivo, 'El motivo', 5, 300, true);
        [$titulo, , $personal] = self::CATALOGO[$dto->tipo];

        $limite = 10 ** 7;
        if ($personal) {
            if (!$u->puede('reportes.exportar')) {
                throw new HttpException('Su rol no puede exportar datos personales', 403);
            }
            $limite = (int) ($this->repository->limiteDetalle()[$u->rol] ?? 10000);
        }

        if ($dto->tipo === 'listado') {
            [$w, $p] = $this->filtros->where($dto->filtros);
            [$amb, $ap] = $this->filtros->ambito($u);
            $where = $w . $amb;
            $params = array_merge($p, $ap);
            $total = $this->repository->contarListado($where, $params, $limite + 1);
            if ($total > $limite) {
                throw new HttpException('El listado supera el máximo de ' . number_format($limite, 0, ',', '.') . ' filas para su rol. Agregue filtros (departamento, municipio, estado…).', 413);
            }
            $cols = ['expediente', 'nombre', 'dpi', 'fecha_nacimiento', 'edad', 'departamento', 'municipio', 'telefono', 'direccion', 'fecha_solicitud',
                'estado', 'fecha_estado', 'nomina', 'fecha_nomina_desde', 'fecha_nomina_hasta', 'ult_visita_fecha', 'ult_resolucion_codigo',
                'ult_resolucion_fecha', 'fecha_suspension', 'fecha_fallecimiento', 'ult_observacion'];
            $filas = $this->listado($u, $where, $params, $limite);
            $cantidad = $total;
        } else {
            [$aw, $ap] = $this->ambitoCubo($u);
            $stmt = $dto->tipo === 'serie_mensual'
                ? $this->repository->serieMensual($aw, $ap, $dto->filtros)
                : $this->repository->agregado($dto->tipo, $aw, $ap, $dto->filtros);
            $cols = [];
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $cols[] = $stmt->getColumnMeta($i)['name'];
            }
            $filas = $stmt->fetchAll(\PDO::FETCH_NUM);
            $cantidad = count($filas);
        }

        $this->auditoria->registrar($u, 'EXPORTAR', 'reportes', 'reporte', $dto->tipo, [
            'formato' => $dto->formato, 'filas' => $cantidad, 'motivo' => $motivo, 'filtros' => $dto->filtros->toArray(),
        ]);

        return [
            'nombre' => 'PAM_' . $dto->tipo . '_' . date('Ymd_Hi') . '.csv',
            'marca' => "Generado por {$u->usuario} ({$u->rolNombre}) el " . date('d/m/Y H:i') . " · Motivo: $motivo · Uso oficial MINTRAB",
            'titulo' => $titulo,
            'cols' => $cols,
            'filas' => $filas,
        ];
    }

    private function listado(Usuario $u, string $where, array $params, int $limite): \Generator
    {
        $verDpi = $u->puede('beneficiarios.ver_dpi');
        try {
            $stmt = $this->repository->listado($where, $params, $limite);
            while ($fila = $stmt->fetch(\PDO::FETCH_NUM)) {
                if (!$verDpi) {
                    $fila[2] = Security::enmascararDpi($fila[2]);
                }
                yield $fila;
            }
        } finally {
            $this->repository->restaurarBuffer();
        }
    }

    private function ambitoCubo(Usuario $u): array
    {
        if ($u->municipioId) {
            return [' AND x.municipio_id = ?', [$u->municipioId]];
        }
        if ($u->departamentoId) {
            return [' AND (x.municipio_id DIV 100) = ?', [$u->departamentoId]];
        }
        return ['', []];
    }
}
