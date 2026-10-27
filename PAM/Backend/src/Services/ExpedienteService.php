<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\CambioEstadoDTO;
use App\DTOs\CasoDTO;
use App\DTOs\ContactoDTO;
use App\DTOs\FiltrosDTO;
use App\DTOs\MovimientoNominaDTO;
use App\DTOs\ObservacionDTO;
use App\DTOs\ResolucionDTO;
use App\DTOs\SolicitudDTO;
use App\DTOs\VisitaDTO;
use App\Entities\Usuario;
use App\Repositories\ExpedienteRepository;
use App\Utils\Database;
use App\Utils\RateLimiter;
use App\Utils\Security;
use App\Utils\Validator;

class ExpedienteService
{
    private const ORDENES = [
        'fsol' => ['v.fecha_solicitud', 'fecha_solicitud'],
        'nombre' => ['v.nombre_norm', 'nombre_norm'],
        'numero' => ['v.numero', 'numero'],
        'actualizado' => ['v.actualizado_en', 'actualizado_en'],
    ];

    private $repository;
    private $filtros;
    private $auditoria;
    private $db;

    public function __construct()
    {
        $this->repository = new ExpedienteRepository();
        $this->filtros = new FiltroService();
        $this->auditoria = new AuditoriaService();
        $this->db = Database::getInstance();
    }

    // ---------------------------------------------------------------- lectura
    public function listar(Usuario $u, FiltrosDTO $fl, string $orden, string $dir, ?string $cursor, int $limite): array
    {
        $config = require __DIR__ . '/../../config/app.php';
        RateLimiter::hit('busq:' . $u->id, $config['busqueda_rpm']);

        if ($fl->regla && !isset(FiltroService::REGLAS[$fl->regla])) {
            throw new HttpException('Regla desconocida', 400);
        }
        if ($orden === 'relevancia') {
            $orden = 'nombre'; // sin similitud de texto en MySQL: se ordena por nombre
        }
        if (!isset(self::ORDENES[$orden])) {
            throw new HttpException('Orden no permitido', 400);
        }
        [$col, $clave] = self::ORDENES[$orden];
        $desc = strtolower($dir) !== 'asc';
        $limite = max(5, min(100, $limite));

        [$w, $p] = $this->filtros->where($fl);
        [$amb, $ap] = $this->filtros->ambito($u);
        $where = $w . $amb;
        $params = array_merge($p, $ap);

        $filas = $this->repository->buscar($where, $params, $col, $desc, $cursor ? $this->decodificarCursor($cursor) : null, $limite + 1);
        $total = $cursor ? null : $this->repository->contar($where, $params);

        $hayMas = count($filas) > $limite;
        $filas = array_slice($filas, 0, $limite);
        $siguiente = null;
        if ($hayMas && $filas) {
            $ultima = $filas[count($filas) - 1];
            $siguiente = $this->codificarCursor([$ultima[$clave], (int) $ultima['id']]);
        }

        return [
            'items' => array_map(fn($r) => $this->fila($r), $filas),
            'siguiente' => $siguiente,
            'total' => $total,
            'total_es_minimo' => $total === 10001,
        ];
    }

    public function detalle(Usuario $u, int $eid): array
    {
        $r = $this->obtener($eid, $u);
        $repo = $this->repository;

        $apoderado = null;
        if ($r['apoderado_id']) {
            $apoderado = $repo->apoderado((int) $r['apoderado_id']);
            if ($apoderado && !$u->puede('beneficiarios.ver_dpi')) {
                $apoderado['numero_id'] = Security::enmascararDpi($apoderado['numero_id']);
            }
        }

        $alertas = [];
        foreach ($repo->reglasAplicables($eid) as $clave => $aplica) {
            if ($aplica && $clave !== 'qm') {
                $alertas[] = ['regla' => $clave, 'titulo' => FiltroService::REGLAS[$clave][1], 'prioridad' => FiltroService::REGLAS[$clave][2]];
            }
        }

        $this->auditoria->registrar($u, 'VER_EXPEDIENTE', 'beneficiarios', 'core.expediente', (string) $eid);

        $persona = array_intersect_key($r, array_flip(['persona_id', 'nombre', 'documento_tipo', 'cui_valido', 'fecha_nacimiento', 'edad', 'telefono', 'direccion']));
        $persona['cui_valido'] = (bool) $persona['cui_valido'];
        $persona['dpi'] = Security::enmascararDpi($r['documento_numero']);

        $expediente = array_intersect_key($r, array_flip([
            'id', 'numero', 'fecha_solicitud', 'estado', 'estado_orden', 'estado_id', 'fecha_estado', 'departamento', 'municipio',
            'municipio_id', 'departamento_id', 'situacion_nomina', 'fecha_nomina_desde', 'fecha_nomina_hasta',
            'fecha_fallecimiento', 'fecha_suspension', 'ult_observacion', 'ult_observacion_fecha', 'actualizado_en',
        ]));

        return [
            'persona' => $persona,
            'expediente' => $expediente,
            'visitas' => $repo->visitas($eid),
            'resoluciones' => $repo->resoluciones($eid),
            'observaciones' => $repo->observaciones($eid),
            'nomina' => $repo->nomina($eid),
            'estados' => $repo->estados($eid),
            'eventos' => $repo->eventos($eid),
            'apoderado' => $apoderado,
            'otros_expedientes' => $repo->otrosExpedientes((int) $r['persona_id'], $eid),
            'alertas' => $alertas,
            'casos' => $repo->casos($eid),
            'puede_ver_dpi' => $u->puede('beneficiarios.ver_dpi'),
        ];
    }

    public function verDpi(Usuario $u, int $eid): array
    {
        $r = $this->obtener($eid, $u);
        $this->auditoria->registrar($u, 'VER_DPI', 'beneficiarios', 'core.expediente', (string) $eid);
        return ['dpi' => $r['documento_numero']];
    }

    // ---------------------------------------------------------------- escritura
    public function agregarObservacion(Usuario $u, int $eid, ObservacionDTO $dto): array
    {
        $texto = Validator::texto($dto->texto, 'La observación', 3, 4000, true);
        $fecha = Validator::fecha($dto->fecha ?? date('Y-m-d'), 'La fecha', false);
        return ['id' => $this->db->transaction(function () use ($u, $eid, $texto, $fecha) {
            $this->obtener($eid, $u);
            $id = $this->repository->crearObservacion($eid, $fecha, $texto, $u->id);
            $this->repository->recalcularResumen($eid);
            return $id;
        })];
    }

    public function agregarVisita(Usuario $u, int $eid, VisitaDTO $dto): array
    {
        $fecha = Validator::fecha($dto->fecha, 'La fecha', false);
        $resultado = Validator::opcion($dto->resultado, ['POSITIVA', 'NEGATIVA', 'NO_LOCALIZADO'], 'El resultado');
        $observacion = Validator::texto($dto->observacion, 'La observación', 0, 4000);
        $tecnico = Validator::texto($dto->tecnico, 'El técnico', 0, 120);
        return ['id' => $this->db->transaction(function () use ($u, $eid, $fecha, $resultado, $observacion, $tecnico) {
            $r = $this->obtener($eid, $u);
            if ($r['fecha_solicitud'] && $fecha < $r['fecha_solicitud']) {
                throw new HttpException('La visita no puede ser anterior a la solicitud', 400);
            }
            $id = $this->repository->crearVisita($eid, $fecha, $resultado, $observacion, $tecnico ?? $u->nombre, $u->id);
            $this->repository->recalcularResumen($eid);
            return $id;
        })];
    }

    public function agregarResolucion(Usuario $u, int $eid, ResolucionDTO $dto): array
    {
        $fecha = Validator::fecha($dto->fecha, 'La fecha', false);
        $numero = Validator::texto($dto->numero, 'El número', 0, 60);
        $descripcion = Validator::texto($dto->descripcion, 'La descripción', 0, 4000);
        return ['id' => $this->db->transaction(function () use ($u, $eid, $dto, $fecha, $numero, $descripcion) {
            $this->obtener($eid, $u);
            $tipoId = $this->repository->tipoResolucionId($dto->tipoCodigo);
            if ($tipoId === null) {
                throw new HttpException('Tipo de resolución no válido', 400);
            }
            $id = $this->repository->crearResolucion($eid, $fecha, $tipoId, $numero, $descripcion, $u->id);
            $this->repository->recalcularResumen($eid);
            return $id;
        })];
    }

    public function cambiarEstado(Usuario $u, int $eid, CambioEstadoDTO $dto): array
    {
        $fecha = Validator::fecha($dto->fecha, 'La fecha', false);
        $motivo = Validator::texto($dto->motivo, 'El motivo', 5, 1000, true);
        $this->db->transaction(function () use ($u, $eid, $dto, $fecha, $motivo) {
            $r = $this->obtener($eid, $u);
            if ($dto->estadoCodigo === 'EN_NOMINA_ACTIVA') {
                throw new HttpException('El ingreso a nómina se registra como movimiento de nómina (ALTA)', 400);
            }
            $this->aplicarEstado($eid, $dto->estadoCodigo, $fecha, $motivo, $u);
            if ((int) $r['estado_id'] === 1) { // sale de nómina
                $this->repository->crearMovimientoNomina($eid, 'BAJA', $fecha, $motivo, $u->id);
            }
            $this->repository->recalcularResumen($eid);
        });
        return ['ok' => true];
    }

    public function movimientoNomina(Usuario $u, int $eid, MovimientoNominaDTO $dto): array
    {
        $tipo = Validator::opcion($dto->tipo, ['ALTA', 'BAJA'], 'El tipo');
        $fecha = Validator::fecha($dto->fecha, 'La fecha', false);
        $motivo = Validator::texto($dto->motivo, 'El motivo', 0, 1000);
        $this->db->transaction(function () use ($u, $eid, $dto, $tipo, $fecha, $motivo) {
            $r = $this->obtener($eid, $u);
            if ($tipo === 'ALTA') {
                if ((int) $r['estado_id'] === 1) {
                    throw new HttpException('El expediente ya está en nómina activa', 400);
                }
                if ($r['fecha_fallecimiento']) {
                    throw new HttpException('No se puede dar de alta a una persona con fallecimiento registrado', 400);
                }
                $this->aplicarEstado($eid, 'EN_NOMINA_ACTIVA', $fecha, $motivo ?? 'Alta en nómina', $u);
            } else {
                if ((int) $r['estado_id'] !== 1) {
                    throw new HttpException('Solo se puede dar de baja un expediente en nómina activa', 400);
                }
                if (!$dto->estadoCodigo || $dto->estadoCodigo === 'EN_NOMINA_ACTIVA') {
                    throw new HttpException('Indique el estado resultante de la baja', 400);
                }
                $this->aplicarEstado($eid, $dto->estadoCodigo, $fecha, $motivo ?? 'Baja de nómina', $u);
            }
            $this->repository->crearMovimientoNomina($eid, $tipo, $fecha, $motivo, $u->id);
            $this->repository->recalcularResumen($eid);
        });
        return ['ok' => true];
    }

    public function actualizarContacto(Usuario $u, int $eid, ContactoDTO $dto): array
    {
        $telefono = Validator::texto($dto->telefono, 'El teléfono', 0, 40);
        $direccion = Validator::texto($dto->direccion, 'La dirección', 0, 400);
        $this->db->transaction(function () use ($u, $eid, $telefono, $direccion) {
            $r = $this->obtener($eid, $u);
            $this->repository->actualizarContacto((int) $r['persona_id'], $telefono, $direccion);
            $this->repository->marcarRefrescoPendiente();
        });
        return ['ok' => true];
    }

    public function nuevaSolicitud(Usuario $u, SolicitudDTO $dto): array
    {
        $nombre = Validator::texto($dto->nombre, 'El nombre', 5, 200, true);
        $dpi = preg_replace('/\D/', '', (string) $dto->dpi) ?: null;
        if ($dpi !== null && strlen($dpi) > 20) {
            throw new HttpException('El documento no puede superar 20 dígitos', 400);
        }
        $fnac = Validator::fecha($dto->fechaNacimiento, 'La fecha de nacimiento');
        $fsol = Validator::fecha($dto->fechaSolicitud, 'La fecha de solicitud', false);
        $telefono = Validator::texto($dto->telefono, 'El teléfono', 0, 40);
        $direccion = Validator::texto($dto->direccion, 'La dirección', 0, 400);
        $numero = Validator::texto($dto->numero, 'El número de expediente', 0, 60);
        $municipio = Validator::entero($dto->municipioId, 'El municipio');

        if (($u->municipioId && $municipio !== $u->municipioId) || ($u->departamentoId && intdiv($municipio, 100) !== $u->departamentoId)) {
            throw new HttpException('El municipio está fuera de su ámbito', 403);
        }

        $eid = $this->db->transaction(function () use ($u, $nombre, $dpi, $fnac, $fsol, $telefono, $direccion, $numero, $municipio) {
            if (!$this->repository->municipioExiste($municipio)) {
                throw new HttpException('El municipio no es válido', 400);
            }
            $esCui = $dpi !== null && strlen($dpi) === 13;
            if ($esCui && !$this->repository->cuiValido($dpi)) {
                throw new HttpException('El CUI no es válido (dígito verificador o código de departamento/municipio)', 400);
            }
            $nombreNorm = Security::norm($nombre);
            $clave = $esCui ? "CUI:$dpi" : "NF:$nombreNorm|$fnac";
            $tipoDoc = $esCui ? 'CUI' : ($dpi !== null ? 'CEDULA' : 'SIN_DOCUMENTO');

            $personaId = $this->repository->guardarPersona(
                $clave, mb_strtoupper($nombre, 'UTF-8'), $nombreNorm, $tipoDoc, $dpi, $esCui, $fnac, $telefono, $direccion, $municipio
            );
            if ($this->repository->tieneExpedienteVigente($personaId)) {
                throw new HttpException('La persona ya tiene un expediente en trámite o en nómina activa', 409);
            }
            $eid = $this->repository->crearExpediente($numero ?? $dpi, $personaId, $municipio, $fsol);
            if (($numero ?? $dpi) === null) {
                $this->repository->asignarNumero($eid, 'PAM-' . substr($fsol, 0, 4) . '-' . $eid);
            }
            $this->repository->crearEstado($eid, 2, $fsol, 'Solicitud registrada', $u->id);
            $this->repository->recalcularResumen($eid);
            return $eid;
        });
        return ['id' => $eid];
    }

    public function gestionarCaso(Usuario $u, int $eid, CasoDTO $dto): array
    {
        if (!isset(FiltroService::REGLAS[$dto->regla])) {
            throw new HttpException('Regla desconocida', 400);
        }
        $estado = Validator::opcion($dto->estado, ['EN_REVISION', 'CONFIRMADO', 'DESCARTADO', 'CORREGIDO'], 'El estado');
        $comentario = Validator::texto($dto->comentario, 'El comentario', 0, 2000);
        $this->db->transaction(function () use ($u, $eid, $dto, $estado, $comentario) {
            $this->obtener($eid, $u);
            $this->repository->guardarCaso($dto->regla, $eid, $estado, $comentario, $u->id);
        });
        return ['ok' => true];
    }

    // ---------------------------------------------------------------- internos
    /** Expediente dentro del ámbito del usuario, o 404. */
    private function obtener(int $eid, Usuario $u): array
    {
        [$amb, $ap] = $this->filtros->ambito($u);
        $r = $this->repository->obtener($eid, $amb, $ap);
        if (!$r) {
            throw new HttpException('Expediente no encontrado o fuera de su ámbito', 404);
        }
        return $r;
    }

    private function aplicarEstado(int $eid, string $codigo, string $fecha, string $motivo, Usuario $u): void
    {
        $estadoId = $this->repository->estadoId($codigo);
        if ($estadoId === null) {
            throw new HttpException('Estado no válido', 400);
        }
        $this->repository->cerrarEstadoVigente($eid);
        $this->repository->crearEstado($eid, $estadoId, $fecha, $motivo, $u->id);
        if ($codigo === 'FALLECIDO') {
            $this->repository->marcarFallecimiento($eid, $fecha);
        }
        if ($codigo === 'SUSPENDIDO') {
            $this->repository->marcarSuspension($eid, $fecha);
        }
    }

    private function fila(array $r): array
    {
        return [
            'id' => (int) $r['id'], 'numero' => $r['numero'], 'nombre' => $r['nombre'], 'dpi' => Security::enmascararDpi($r['documento_numero']),
            'documento_tipo' => $r['documento_tipo'], 'departamento' => $r['departamento'], 'municipio' => $r['municipio'],
            'municipio_id' => (int) $r['municipio_id'], 'edad' => $r['edad'] === null ? null : (int) $r['edad'],
            'fecha_solicitud' => $r['fecha_solicitud'], 'estado_orden' => (int) $r['estado_orden'], 'estado' => $r['estado'],
            'situacion_nomina' => (int) $r['situacion_nomina'], 'fecha_estado' => $r['fecha_estado'], 'actualizado_en' => $r['actualizado_en'],
        ];
    }

    private function codificarCursor(array $valores): string
    {
        return rtrim(strtr(base64_encode(json_encode($valores)), '+/', '-_'), '=');
    }

    private function decodificarCursor(string $cursor): array
    {
        $d = json_decode(base64_decode(strtr($cursor, '-_', '+/')), true);
        if (!is_array($d) || count($d) !== 2) {
            throw new HttpException('Cursor inválido', 400);
        }
        return $d;
    }
}
