<?php
namespace Models;

use Core\Database;

class JugadorModel {
    const POS_A_API = ['Portero' => 'POR', 'Defensa' => 'DEF', 'Mediocampo' => 'MED', 'Delantero' => 'DEL'];

    public static function posicionBD($api) {
        $inv = array_flip(self::POS_A_API);
        return $inv[$api] ?? null;
    }

    public static function serializar(array $j, $conPrivados = true) {
        $s = [
            'id' => (int)$j['id'], 'team_id' => (int)$j['equipo_id'], 'team_name' => $j['equipo_nombre'],
            'short_name' => $j['abreviatura'] ?: strtoupper(mb_substr(preg_replace('/\s+/', '', $j['equipo_nombre']), 0, 3)),
            'primary_color' => $j['color_principal'],
            'full_name' => $j['nombre'], 'position' => self::POS_A_API[$j['posicion']] ?? null,
            'jersey_number' => $j['dorsal'] === null ? null : (int)$j['dorsal'],
            'birth_date' => $j['fecha_nacimiento'], 'nationality' => $j['nacionalidad'],
            'height_cm' => $j['estatura_cm'] === null ? null : (int)$j['estatura_cm'],
            'weight_kg' => $j['peso_kg'] === null ? null : (float)$j['peso_kg'],
            'preferred_foot' => $j['pie_habil'], 'photo_url' => $j['foto_ruta'] ?: null,
            'status' => $j['estado'],
        ];
        if ($conPrivados) $s += ['dpi' => $j['dpi'], 'telefono' => $j['telefono'], 'razon_baja' => $j['razon_baja'], 'fecha_baja' => $j['fecha_baja']];
        return $s;
    }

    private static function select() {
        return 'SELECT j.*, e.nombre AS equipo_nombre, e.abreviatura, e.color_principal FROM jugadores j JOIN equipos e ON e.id = j.equipo_id';
    }

    public static function lista($equipoId = null) {
        $pdo = Database::connection();
        if ($equipoId) {
            $stmt = $pdo->prepare(self::select() . ' WHERE j.equipo_id = ? ORDER BY j.estado, j.nombre');
            $stmt->execute([$equipoId]);
            return $stmt->fetchAll();
        }
        return $pdo->query(self::select() . ' ORDER BY e.nombre, j.estado, j.nombre')->fetchAll();
    }

    // Jugadores activos de varios equipos (para armar eventos de un partido). Sin datos privados.
    public static function deEquipos(array $ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(self::select() . " WHERE j.equipo_id IN ($in) AND j.estado = 'activo' ORDER BY e.nombre, j.nombre");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public static function porId($id) {
        $stmt = Database::connection()->prepare(self::select() . ' WHERE j.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function crear(array $datos) {
        $pdo = Database::connection();
        $cols = array_keys($datos);
        $pdo->prepare('INSERT INTO jugadores (' . implode(',', $cols) . ', foto_ruta) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ", '')")
            ->execute(array_values($datos));
        return (int)$pdo->lastInsertId();
    }

    public static function actualizar($id, array $datos) {
        if (!$datos) return;
        $set = implode(', ', array_map(function ($c) { return "$c = ?"; }, array_keys($datos)));
        Database::connection()->prepare("UPDATE jugadores SET $set WHERE id = ?")->execute(array_merge(array_values($datos), [$id]));
    }

    public static function tieneEventos($id) {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM eventos_partido WHERE jugador_id = ? OR asistente_id = ?');
        $stmt->execute([$id, $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function eliminar($id) {
        Database::connection()->prepare('DELETE FROM jugadores WHERE id = ?')->execute([$id]);
    }
}
