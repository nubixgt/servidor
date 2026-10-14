<?php
namespace Controllers;

use Core\HttpException;
use Core\Request;
use Core\Response;
use Models\EquipoModel;
use Models\JugadorModel;
use Models\PartidoModel;

class PartidosController extends BaseController {
    public function lista() {
        $this->autenticar();
        Response::json(array_map(['Models\PartidoModel', 'serializar'], PartidoModel::lista($this->equipoPermitido())));
    }

    public function ver($p) {
        $this->autenticar();
        $partido = $this->cargar($p['id']);
        Response::json([
            'match' => PartidoModel::serializar($partido),
            'events' => array_map(['Models\PartidoModel', 'serializarEvento'], PartidoModel::eventos($partido['id'])),
        ]);
    }

    public function crear() {
        $this->soloAdmin();
        $d = $this->validar(Request::input());
        $id = PartidoModel::crear($d, $this->usuario['id']);
        Response::json(PartidoModel::serializar(PartidoModel::porId($id)), 201);
    }

    public function actualizar($p) {
        $this->autenticar();
        $actual = $this->cargar($p['id']);
        $d = $this->validar(Request::input());
        $this->exigirParticipacion($d);

        if (PartidoModel::tieneEventos($actual['id'])
            && ($d['estado'] === 'programado' || $d['equipo_local_id'] !== (int)$actual['equipo_local_id'] || $d['equipo_visitante_id'] !== (int)$actual['equipo_visitante_id'])) {
            throw new HttpException('El partido tiene eventos: no se pueden cambiar equipos ni volver a Programado.', 409);
        }
        PartidoModel::actualizar($actual['id'], $d);
        Response::json(PartidoModel::serializar(PartidoModel::porId($actual['id'])));
    }

    public function eliminar($p) {
        $this->soloAdmin();
        $partido = $this->cargar($p['id']);
        PartidoModel::eliminar($partido['id']);
        Response::json(['message' => 'Partido eliminado.']);
    }

    // ---- eventos ----
    public function eventos($p) {
        $this->autenticar();
        $partido = $this->cargar($p['id']);
        Response::json(array_map(['Models\PartidoModel', 'serializarEvento'], PartidoModel::eventos($partido['id'])));
    }

    // Jugadores de los dos equipos del partido (el encargado solo ve su equipo en otras pantallas).
    public function jugadores($p) {
        $this->autenticar();
        $partido = $this->cargar($p['id']);
        $filas = JugadorModel::deEquipos([(int)$partido['equipo_local_id'], (int)$partido['equipo_visitante_id']]);
        Response::json(array_map(function ($j) { return JugadorModel::serializar($j, false); }, $filas));
    }

    public function crearEvento($p) {
        $this->autenticar();
        $partido = $this->cargar($p['id']);
        $id = PartidoModel::guardarEvento(null, $this->validarEvento($partido, Request::input()) + ['partido_id' => (int)$partido['id']]);
        Response::json(PartidoModel::serializarEvento(PartidoModel::eventoPorId($id)), 201);
    }

    public function actualizarEvento($p) {
        $this->autenticar();
        $partido = $this->cargar($p['id']);
        $ev = PartidoModel::eventoPorId($p['eid']);
        if (!$ev || (int)$ev['partido_id'] !== (int)$partido['id']) throw new HttpException('Evento no encontrado.', 404);
        PartidoModel::guardarEvento((int)$ev['id'], $this->validarEvento($partido, Request::input()));
        Response::json(PartidoModel::serializarEvento(PartidoModel::eventoPorId($ev['id'])));
    }

    public function eliminarEvento($p) {
        $this->autenticar();
        $partido = $this->cargar($p['id']);
        $ev = PartidoModel::eventoPorId($p['eid']);
        if (!$ev || (int)$ev['partido_id'] !== (int)$partido['id']) throw new HttpException('Evento no encontrado.', 404);
        PartidoModel::eliminarEvento((int)$ev['id']);
        Response::json(['message' => 'Evento eliminado.']);
    }

    // ---- internos ----
    // Carga el partido y exige que el encargado juegue en él.
    private function cargar($id) {
        $p = PartidoModel::porId($id);
        if (!$p) throw new HttpException('Partido no encontrado.', 404);
        if (!$this->esAdmin() && !in_array((int)$this->usuario['equipo_id'], [(int)$p['equipo_local_id'], (int)$p['equipo_visitante_id']], true)) {
            throw new HttpException('No tienes permisos sobre este partido.', 403);
        }
        return $p;
    }

    private function exigirParticipacion(array $d) {
        if (!$this->esAdmin() && !in_array((int)$this->usuario['equipo_id'], [$d['equipo_local_id'], $d['equipo_visitante_id']], true)) {
            throw new HttpException('Solo puedes registrar partidos de tu equipo.', 403);
        }
    }

    private function validar(array $in) {
        $local = $this->entero($in, 'equipo_local_id', 1, PHP_INT_MAX, true, 'equipo local');
        $visita = $this->entero($in, 'equipo_visitante_id', 1, PHP_INT_MAX, true, 'equipo visitante');
        if ($local === $visita) throw new HttpException('Local y visitante deben ser distintos.', 422, ['equipo_visitante_id' => 'Debe ser distinto del local.']);
        foreach ([$local, $visita] as $eq) {
            if (!EquipoModel::porId($eq)) throw new HttpException('Uno de los equipos no existe.', 422);
        }
        $fecha = $this->texto($in, 'fecha', 19, true, 'fecha');
        $f = \DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
        if (!$f || $f->format('Y-m-d H:i:s') !== $fecha) throw new HttpException('Fecha y hora no válidas (usa AAAA-MM-DD HH:MM:SS).', 422, ['fecha' => 'Fecha no válida.']);
        return [
            'equipo_local_id' => $local, 'equipo_visitante_id' => $visita, 'fecha' => $fecha,
            'jornada' => $this->entero($in, 'jornada', 1, 99, true),
            'estado' => $this->enumerado($in, 'estado', ['programado', 'en_juego', 'finalizado'], true),
        ];
    }

    private function validarEvento(array $partido, array $in) {
        if ($partido['estado'] === 'programado') throw new HttpException('Cambia el partido a En juego antes de registrar eventos.', 409);
        $tipo = $this->enumerado($in, 'tipo', array_keys(PartidoModel::TIPO_A_API), true);
        $minuto = $this->entero($in, 'minuto', 0, 130, true);
        $jugadorId = $this->entero($in, 'jugador_id', 1, PHP_INT_MAX, true, 'jugador');
        $jugador = JugadorModel::porId($jugadorId);
        if (!$jugador || !in_array((int)$jugador['equipo_id'], [(int)$partido['equipo_local_id'], (int)$partido['equipo_visitante_id']], true)) {
            throw new HttpException('Jugador no válido para este partido.', 422, ['jugador_id' => 'Jugador no válido.']);
        }
        $asistenteId = $this->entero($in, 'asistente_id', 1, PHP_INT_MAX, false, 'asistente');
        if ($asistenteId !== null) {
            $a = JugadorModel::porId($asistenteId);
            if ($tipo !== 'gol' || !$a || (int)$a['equipo_id'] !== (int)$jugador['equipo_id'] || $asistenteId === $jugadorId) {
                throw new HttpException('Asistencia inválida.', 422, ['asistente_id' => 'Asistencia inválida.']);
            }
        }
        return ['equipo_id' => (int)$jugador['equipo_id'], 'jugador_id' => $jugadorId, 'asistente_id' => $asistenteId, 'tipo' => $tipo, 'minuto' => $minuto];
    }
}
