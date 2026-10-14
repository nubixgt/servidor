<?php
namespace Controllers;

use Core\Database;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Core\Uploads;
use Models\EquipoModel;
use Models\UsuarioModel;

class EquiposController extends BaseController {
    // Fotos: campo del formulario => [columna, nombre de archivo, bandera para quitarla]
    private $fotos = [
        'foto' => ['foto_ruta', 'escudo', 'eliminar_foto', 'escudo'],
        'foto_representante' => ['foto_representante_ruta', 'representante', 'eliminar_foto_representante', 'foto del representante'],
        'foto_sub_representante' => ['sub_representante_foto_ruta', 'sub_representante', 'eliminar_foto_sub_representante', 'foto del sub-representante'],
    ];
    // Campos que un encargado NO puede cambiar (identidad del equipo).
    private $soloAdmin = ['nombre', 'representante', 'dpi', 'categoria'];

    public function lista() {
        $this->autenticar();
        $filas = EquipoModel::todos();
        if (!$this->esAdmin()) {
            // El encargado ve su equipo completo y de los demás solo lo público (para armar partidos).
            $propio = $this->usuario['equipo_id'];
            Response::json(array_map(function ($e) use ($propio) { return EquipoModel::serializar($e, (int)$e['id'] === $propio); }, $filas));
        }
        Response::json(array_map(function ($e) { return EquipoModel::serializar($e); }, $filas));
    }

    public function ver($p) {
        $this->autenticar();
        $this->exigirEquipo($p['id']);
        $e = EquipoModel::porId($p['id']);
        if (!$e) throw new HttpException('Equipo no encontrado.', 404);
        Response::json(EquipoModel::serializar($e));
    }

    public function crear() {
        $this->soloAdmin();
        $in = Request::input();
        $datos = $this->validar($in, true);

        $pdo = Database::connection();
        $subidas = [];
        $pdo->beginTransaction();
        try {
            $dpiUsuario = preg_replace('/\s+/', '', $datos['dpi']);
            if (UsuarioModel::existe($dpiUsuario)) throw new HttpException('Ya existe un usuario con ese DPI.', 409, ['dpi' => 'Ya existe un usuario con ese DPI.']);

            try {
                $id = EquipoModel::crear($datos);
            } catch (\PDOException $e) {
                $this->duplicado($e, ['dpi' => ['dpi', 'Ya existe un equipo con ese DPI.']]);
            }
            // Acceso del encargado: usuario = DPI y contraseña = teléfono (sin espacios), igual que hasta ahora.
            UsuarioModel::crearEncargado($dpiUsuario, password_hash(preg_replace('/\s+/', '', $datos['telefono']), PASSWORD_DEFAULT), $id);

            $cambios = $this->guardarFotos($id, null, $subidas);
            EquipoModel::actualizar($id, $cambios);
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            foreach ($subidas as $ruta) Uploads::delete($ruta);
            throw $e;
        }
        Response::json(EquipoModel::serializar(EquipoModel::porId($id)), 201);
    }

    public function actualizar($p) {
        $this->autenticar();
        $id = (int)$p['id'];
        $this->exigirEquipo($id);
        $actual = EquipoModel::porId($id);
        if (!$actual) throw new HttpException('Equipo no encontrado.', 404);

        $in = Request::input();
        $datos = $this->validar($in, false, $id);
        if (!$this->esAdmin()) {
            foreach ($this->soloAdmin as $c) unset($datos[$c]);
        }

        $subidas = [];
        try {
            $cambios = $this->guardarFotos($id, $actual, $subidas);
            EquipoModel::actualizar($id, $datos + $cambios);
        } catch (\PDOException $e) {
            foreach ($subidas as $ruta) Uploads::delete($ruta);
            $this->duplicado($e, ['dpi' => ['dpi', 'Ya existe un equipo con ese DPI.']]);
        } catch (\Exception $e) {
            foreach ($subidas as $ruta) Uploads::delete($ruta);
            throw $e;
        }
        // Borrar fotos reemplazadas solo después de guardar bien.
        foreach ($this->fotos as $campo => $f) {
            if (array_key_exists($f[0], $cambios) && $actual[$f[0]] && $actual[$f[0]] !== $cambios[$f[0]]) Uploads::delete($actual[$f[0]]);
        }
        Response::json(EquipoModel::serializar(EquipoModel::porId($id)));
    }

    public function eliminar($p) {
        $this->soloAdmin();
        $id = (int)$p['id'];
        $e = EquipoModel::porId($id);
        if (!$e) throw new HttpException('Equipo no encontrado.', 404);
        if (EquipoModel::tieneRelaciones($id)) throw new HttpException('El equipo tiene jugadores o partidos relacionados.', 409);
        EquipoModel::eliminar($id);
        foreach ($this->fotos as $f) Uploads::delete($e[$f[0]]);
        Response::json(['message' => 'Equipo eliminado.']);
    }

    // ---- internos ----
    private function validar(array $in, $crear, $id = 0) {
        $d = [];
        $d['nombre'] = $this->texto($in, 'nombre', 100, $crear, 'nombre');
        $d['representante'] = $this->texto($in, 'representante', 100, $crear, 'representante');
        $d['telefono'] = $this->texto($in, 'telefono', 20, $crear, 'teléfono');
        $d['dpi'] = $this->texto($in, 'dpi', 20, $crear, 'DPI');
        $d['abreviatura'] = $this->texto($in, 'abreviatura', 4);
        if ($d['abreviatura'] !== null) $d['abreviatura'] = strtoupper($d['abreviatura']);
        $d['categoria'] = $this->enumerado($in, 'categoria', ['A', 'B'], $crear);
        $d['ciudad'] = $this->texto($in, 'ciudad', 100);
        $d['estadio'] = $this->texto($in, 'estadio', 100);
        $d['entrenador'] = $this->texto($in, 'entrenador', 100);
        $d['anio_fundacion'] = $this->entero($in, 'anio_fundacion', 1800, (int)date('Y'), false, 'año de fundación');
        $d['sub_representante_nombre'] = $this->texto($in, 'sub_representante_nombre', 150);
        $d['sub_representante_dpi'] = $this->texto($in, 'sub_representante_dpi', 13);
        $d['sub_representante_telefono'] = $this->texto($in, 'sub_representante_telefono', 8);
        foreach (['color_principal' => '#c6ff3b', 'color_secundario' => '#101518'] as $c => $defecto) {
            $v = $this->texto($in, $c, 7);
            if ($v !== null && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) throw new HttpException("Color no válido en $c.", 422, [$c => 'Usa el formato #RRGGBB.']);
            $d[$c] = $v ?? $defecto;
        }

        if ($d['nombre'] !== null && EquipoModel::nombreRepetido($d['nombre'], $id)) {
            throw new HttpException('Ya existe un equipo con ese nombre.', 409, ['nombre' => 'Ya existe un equipo con ese nombre.']);
        }
        // En actualización solo se tocan los campos que llegaron en la solicitud.
        if (!$crear) {
            foreach (array_keys($d) as $col) {
                if (!array_key_exists($col, $in)) unset($d[$col]);
            }
        }
        return $d;
    }

    // Guarda las fotos nuevas / marca las eliminadas. Devuelve [columna => ruta]. $subidas: rutas creadas (para deshacer).
    private function guardarFotos($id, $actual, array &$subidas) {
        $cambios = [];
        foreach ($this->fotos as $campo => $f) {
            list($col, $nombre, $bandera, $etiqueta) = $f;
            if (Uploads::has($campo)) {
                $ruta = Uploads::save($campo, "equipos/$id", $nombre . '_' . time(), $etiqueta);
                $subidas[] = $ruta;
                $cambios[$col] = $ruta;
            } elseif (!empty($_POST[$bandera]) && $actual) {
                $cambios[$col] = $col === 'foto_ruta' ? '' : null;
            }
        }
        return $cambios;
    }
}
