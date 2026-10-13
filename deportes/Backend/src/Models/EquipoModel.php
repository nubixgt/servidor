<?php
namespace Models;

use Core\Database;

class EquipoModel {
    // Forma Pulse + datos oficiales de contacto. Las rutas de foto son relativas ("uploads/...").
    public static function serializar(array $e, $conContacto = true) {
        $s = [
            'id' => (int)$e['id'],
            'name' => $e['nombre'],
            'short_name' => $e['abreviatura'] ?: strtoupper(mb_substr(preg_replace('/\s+/', '', $e['nombre']), 0, 3)),
            'city' => $e['ciudad'], 'stadium' => $e['estadio'], 'coach' => $e['entrenador'],
            'primary_color' => $e['color_principal'], 'secondary_color' => $e['color_secundario'],
            'founded_year' => $e['anio_fundacion'] === null ? null : (int)$e['anio_fundacion'],
            'logo_url' => $e['foto_ruta'] ?: null,
            'players_count' => isset($e['jugadores_activos']) ? (int)$e['jugadores_activos'] : null,
        ];
        if ($conContacto) {
            $s += [
                'representante' => $e['representante'], 'telefono' => $e['telefono'], 'dpi' => $e['dpi'],
                'foto_representante_url' => $e['foto_representante_ruta'] ?: null,
                'sub_representante_nombre' => $e['sub_representante_nombre'],
                'sub_representante_dpi' => $e['sub_representante_dpi'],
                'sub_representante_telefono' => $e['sub_representante_telefono'],
                'sub_representante_foto_url' => $e['sub_representante_foto_ruta'] ?: null,
                'usuario' => $e['usuario'] ?? null,
            ];
        }
        return $s;
    }

    private static function select() {
        return "SELECT e.*, (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id = e.id AND j.estado = 'activo') AS jugadores_activos,
                       (SELECT u.usuario FROM usuarios u WHERE u.equipo_id = e.id AND u.rol = 'encargado' LIMIT 1) AS usuario
                FROM equipos e";
    }

    public static function todos() {
        return Database::connection()->query(self::select() . ' ORDER BY e.nombre')->fetchAll();
    }

    public static function porId($id) {
        $stmt = Database::connection()->prepare(self::select() . ' WHERE e.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function nombreRepetido($nombre, $exceptoId = 0) {
        $stmt = Database::connection()->prepare('SELECT 1 FROM equipos WHERE LOWER(nombre) = LOWER(?) AND id <> ?');
        $stmt->execute([$nombre, $exceptoId]);
        return (bool)$stmt->fetchColumn();
    }

    // $datos: columna => valor. Devuelve el id.
    public static function crear(array $datos) {
        $pdo = Database::connection();
        $cols = array_keys($datos);
        $pdo->prepare('INSERT INTO equipos (' . implode(',', $cols) . ', foto_ruta) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ", '')")
            ->execute(array_values($datos));
        return (int)$pdo->lastInsertId();
    }

    public static function actualizar($id, array $datos) {
        if (!$datos) return;
        $set = implode(', ', array_map(function ($c) { return "$c = ?"; }, array_keys($datos)));
        Database::connection()->prepare("UPDATE equipos SET $set WHERE id = ?")->execute(array_merge(array_values($datos), [$id]));
    }

    public static function tieneRelaciones($id) {
        $pdo = Database::connection();
        $j = $pdo->prepare('SELECT COUNT(*) FROM jugadores WHERE equipo_id = ?'); $j->execute([$id]);
        $p = $pdo->prepare('SELECT COUNT(*) FROM partidos WHERE equipo_local_id = ? OR equipo_visitante_id = ?'); $p->execute([$id, $id]);
        return ((int)$j->fetchColumn() + (int)$p->fetchColumn()) > 0;
    }

    public static function eliminar($id) {
        Database::connection()->prepare('DELETE FROM equipos WHERE id = ?')->execute([$id]); // usuarios: ON DELETE CASCADE
    }
}
