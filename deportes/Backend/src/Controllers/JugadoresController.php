<?php
namespace Controllers;

use Core\Database;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Core\Uploads;
use Models\EquipoModel;
use Models\JugadorModel;

class JugadoresController extends BaseController {
    public function lista() {
        $this->autenticar();
        $filas = JugadorModel::lista($this->equipoPermitido());
        Response::json(array_map(function ($j) { return JugadorModel::serializar($j); }, $filas));
    }

    public function ver($p) {
        $this->autenticar();
        $j = $this->cargar($p['id']);
        Response::json(JugadorModel::serializar($j));
    }

    public function crear() {
        $this->autenticar();
        $in = Request::input();
        $equipoId = $this->esAdmin() ? $this->entero($in, 'equipo_id', 1, PHP_INT_MAX, true, 'equipo') : $this->usuario['equipo_id'];
        if (!EquipoModel::porId($equipoId)) throw new HttpException('El equipo no existe.', 422, ['equipo_id' => 'Equipo no válido.']);

        $datos = $this->validar($in, true) + ['equipo_id' => $equipoId];
        $subida = null;
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            try {
                $id = JugadorModel::crear($datos);
            } catch (\PDOException $e) {
                $this->duplicado($e, $this->indicesUnicos());
            }
            if (Uploads::has('foto')) {
                $subida = Uploads::save('foto', "jugadores/$id", 'foto_' . time(), 'foto del jugador');
                JugadorModel::actualizar($id, ['foto_ruta' => $subida]);
            }
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            if ($subida) Uploads::delete($subida);
            throw $e;
        }
        Response::json(JugadorModel::serializar(JugadorModel::porId($id)), 201);
    }

    public function actualizar($p) {
        $this->autenticar();
        $actual = $this->cargar($p['id']);
        $in = Request::input();
        $datos = $this->validar($in, false);

        if ($this->esAdmin() && array_key_exists('equipo_id', $in)) {
            $nuevoEquipo = $this->entero($in, 'equipo_id', 1, PHP_INT_MAX, true, 'equipo');
            if (!EquipoModel::porId($nuevoEquipo)) throw new HttpException('El equipo no existe.', 422, ['equipo_id' => 'Equipo no válido.']);
            if ($nuevoEquipo !== (int)$actual['equipo_id']) {
                if (JugadorModel::tieneEventos($actual['id'])) {
                    throw new HttpException('No se puede transferir un jugador con eventos.', 409, ['equipo_id' => 'Tiene eventos registrados.']);
                }
                $datos['equipo_id'] = $nuevoEquipo;
            }
        }

        $subida = null;
        try {
            if (Uploads::has('foto')) {
                $subida = Uploads::save('foto', 'jugadores/' . $actual['id'], 'foto_' . time(), 'foto del jugador');
                $datos['foto_ruta'] = $subida;
            } elseif (!empty($_POST['eliminar_foto'])) {
                $datos['foto_ruta'] = '';
            }
            JugadorModel::actualizar($actual['id'], $datos);
        } catch (\PDOException $e) {
            if ($subida) Uploads::delete($subida);
            $this->duplicado($e, $this->indicesUnicos());
        } catch (\Exception $e) {
            if ($subida) Uploads::delete($subida);
            throw $e;
        }
        if (isset($datos['foto_ruta']) && $actual['foto_ruta'] && $actual['foto_ruta'] !== $datos['foto_ruta']) Uploads::delete($actual['foto_ruta']);
        Response::json(JugadorModel::serializar(JugadorModel::porId($actual['id'])));
    }

    public function baja($p) {
        $this->autenticar();
        $j = $this->cargar($p['id']);
        $razon = $this->texto(Request::input(), 'razon_baja', 500, true, 'motivo de baja');
        if (mb_strlen($razon) < 3) throw new HttpException('Describe el motivo de la baja.', 422, ['razon_baja' => 'Muy corto.']);
        JugadorModel::actualizar($j['id'], ['estado' => 'inactivo', 'razon_baja' => $razon, 'fecha_baja' => date('Y-m-d H:i:s')]);
        Response::json(JugadorModel::serializar(JugadorModel::porId($j['id'])));
    }

    public function alta($p) {
        $this->autenticar();
        $j = $this->cargar($p['id']);
        JugadorModel::actualizar($j['id'], ['estado' => 'activo', 'razon_baja' => null, 'fecha_baja' => null]);
        Response::json(JugadorModel::serializar(JugadorModel::porId($j['id'])));
    }

    public function eliminar($p) {
        $this->soloAdmin();
        $j = $this->cargar($p['id']);
        if (JugadorModel::tieneEventos($j['id'])) throw new HttpException('El jugador tiene eventos relacionados.', 409);
        JugadorModel::eliminar($j['id']);
        Uploads::delete($j['foto_ruta']);
        Response::json(['message' => 'Jugador eliminado.']);
    }

    // ---- internos ----
    private function cargar($id) {
        $j = JugadorModel::porId($id);
        if (!$j) throw new HttpException('Jugador no encontrado.', 404);
        $this->exigirEquipo($j['equipo_id']);
        return $j;
    }

    private function indicesUnicos() {
        return ['dpi' => ['dpi', 'Ya existe un jugador con ese DPI.'], 'uq_jugadores_equipo_dorsal' => ['dorsal', 'Este dorsal ya existe en el equipo.']];
    }

    private function validar(array $in, $crear) {
        $d = [];
        $d['nombre'] = $this->texto($in, 'nombre', 100, $crear, 'nombre');
        $d['dpi'] = $this->texto($in, 'dpi', 20, $crear, 'DPI');
        $d['telefono'] = $this->texto($in, 'telefono', 20, $crear, 'teléfono');
        $pos = $this->enumerado($in, 'posicion', array_keys(JugadorModel::POS_A_API), $crear);
        if ($pos !== null) $d['posicion'] = $pos;
        $d['dorsal'] = $this->entero($in, 'dorsal', 1, 99, false);
        $d['nacionalidad'] = $this->texto($in, 'nacionalidad', 60) ?? 'Guatemala';
        $d['estatura_cm'] = $this->entero($in, 'estatura_cm', 100, 230, false, 'estatura');
        $d['pie_habil'] = $this->enumerado($in, 'pie_habil', ['Derecho', 'Izquierdo', 'Ambidiestro']);

        $peso = $this->texto($in, 'peso_kg', 7);
        if ($peso !== null && (!is_numeric($peso) || $peso < 30 || $peso > 200)) throw new HttpException('El peso debe estar entre 30 y 200 kg.', 422, ['peso_kg' => 'Entre 30 y 200.']);
        $d['peso_kg'] = $peso === null ? null : (float)$peso;

        $nac = $this->texto($in, 'fecha_nacimiento', 10);
        if ($nac !== null) {
            $f = \DateTime::createFromFormat('Y-m-d', $nac);
            if (!$f || $f->format('Y-m-d') !== $nac || $nac < '1900-01-01' || $nac > date('Y-m-d')) {
                throw new HttpException('Fecha de nacimiento no válida.', 422, ['fecha_nacimiento' => 'Fecha no válida.']);
            }
        }
        $d['fecha_nacimiento'] = $nac;

        // En actualización solo se tocan los campos enviados.
        if (!$crear) {
            foreach (array_keys($d) as $col) {
                if (!array_key_exists($col, $in)) unset($d[$col]);
            }
        }
        return $d;
    }
}
