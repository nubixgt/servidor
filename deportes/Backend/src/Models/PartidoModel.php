<?php
namespace Models;

use Core\Database;

class PartidoModel {
    const ESTADO_A_API = ['programado' => 'scheduled', 'en_juego' => 'live', 'finalizado' => 'finished'];
    const TIPO_A_API = ['gol' => 'goal', 'autogol' => 'own_goal', 'amarilla' => 'yellow', 'roja' => 'red'];

    public static function estadoBD($api) { return array_flip(self::ESTADO_A_API)[$api] ?? null; }
    public static function tipoBD($api) { return array_flip(self::TIPO_A_API)[$api] ?? null; }

    // El marcador sale de los eventos: gol suma al equipo del jugador; autogol al rival.
    private static function select() {
        return "SELECT p.*, l.nombre AS local_nombre, l.abreviatura AS local_abrev, l.color_principal AS local_color, l.foto_ruta AS local_logo, l.estadio AS local_estadio,
                       v.nombre AS visita_nombre, v.abreviatura AS visita_abrev, v.color_principal AS visita_color, v.foto_ruta AS visita_logo,
                       (SELECT COUNT(*) FROM eventos_partido ev WHERE ev.partido_id = p.id AND ((ev.tipo = 'gol' AND ev.equipo_id = p.equipo_local_id) OR (ev.tipo = 'autogol' AND ev.equipo_id = p.equipo_visitante_id))) AS goles_local,
                       (SELECT COUNT(*) FROM eventos_partido ev WHERE ev.partido_id = p.id AND ((ev.tipo = 'gol' AND ev.equipo_id = p.equipo_visitante_id) OR (ev.tipo = 'autogol' AND ev.equipo_id = p.equipo_local_id))) AS goles_visita
                FROM partidos p JOIN equipos l ON l.id = p.equipo_local_id JOIN equipos v ON v.id = p.equipo_visitante_id";
    }

    private static function corto($abrev, $nombre) {
        return $abrev ?: strtoupper(mb_substr(preg_replace('/\s+/', '', $nombre), 0, 3));
    }

    public static function serializar(array $p) {
        return [
            'id' => (int)$p['id'],
            'home_team_id' => (int)$p['equipo_local_id'], 'away_team_id' => (int)$p['equipo_visitante_id'],
            'kickoff_at' => $p['fecha'], 'round_number' => (int)$p['jornada'], 'status' => self::ESTADO_A_API[$p['estado']],
            'home_score' => (int)$p['goles_local'], 'away_score' => (int)$p['goles_visita'],
            'home_name' => $p['local_nombre'], 'home_short' => self::corto($p['local_abrev'], $p['local_nombre']),
            'home_color' => $p['local_color'], 'home_logo' => $p['local_logo'] ?: null,
            'away_name' => $p['visita_nombre'], 'away_short' => self::corto($p['visita_abrev'], $p['visita_nombre']),
            'away_color' => $p['visita_color'], 'away_logo' => $p['visita_logo'] ?: null,
            'stadium' => $p['local_estadio'],
        ];
    }

    public static function lista($equipoId = null) {
        $pdo = Database::connection();
        if ($equipoId) {
            $stmt = $pdo->prepare(self::select() . ' WHERE p.equipo_local_id = ? OR p.equipo_visitante_id = ? ORDER BY p.fecha DESC, p.id DESC');
            $stmt->execute([$equipoId, $equipoId]);
            return $stmt->fetchAll();
        }
        return $pdo->query(self::select() . ' ORDER BY p.fecha DESC, p.id DESC')->fetchAll();
    }

    public static function porId($id) {
        $stmt = Database::connection()->prepare(self::select() . ' WHERE p.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function crear(array $d, $usuarioId) {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO partidos (fecha, equipo_local_id, equipo_visitante_id, jornada, estado, registrado_por) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$d['fecha'], $d['equipo_local_id'], $d['equipo_visitante_id'], $d['jornada'], $d['estado'], $usuarioId]);
        return (int)$pdo->lastInsertId();
    }

    public static function actualizar($id, array $d) {
        Database::connection()->prepare('UPDATE partidos SET fecha = ?, equipo_local_id = ?, equipo_visitante_id = ?, jornada = ?, estado = ? WHERE id = ?')
            ->execute([$d['fecha'], $d['equipo_local_id'], $d['equipo_visitante_id'], $d['jornada'], $d['estado'], $id]);
    }

    public static function eliminar($id) {
        Database::connection()->prepare('DELETE FROM partidos WHERE id = ?')->execute([$id]); // eventos: ON DELETE CASCADE
    }

    public static function tieneEventos($id) {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM eventos_partido WHERE partido_id = ?');
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ---- eventos ----
    public static function serializarEvento(array $e) {
        return [
            'id' => (int)$e['id'], 'match_id' => (int)$e['partido_id'], 'team_id' => (int)$e['equipo_id'],
            'player_id' => (int)$e['jugador_id'], 'assist_player_id' => $e['asistente_id'] === null ? null : (int)$e['asistente_id'],
            'type' => self::TIPO_A_API[$e['tipo']], 'minute' => (int)$e['minuto'],
            'player_name' => $e['jugador_nombre'], 'assist_name' => $e['asistente_nombre'], 'team_name' => $e['equipo_nombre'],
        ];
    }

    private static function selectEventos() {
        return 'SELECT ev.*, j.nombre AS jugador_nombre, a.nombre AS asistente_nombre, e.nombre AS equipo_nombre
                FROM eventos_partido ev JOIN jugadores j ON j.id = ev.jugador_id LEFT JOIN jugadores a ON a.id = ev.asistente_id JOIN equipos e ON e.id = ev.equipo_id';
    }

    public static function eventos($partidoId) {
        $stmt = Database::connection()->prepare(self::selectEventos() . ' WHERE ev.partido_id = ? ORDER BY ev.minuto, ev.id');
        $stmt->execute([$partidoId]);
        return $stmt->fetchAll();
    }

    // Todos los eventos de la liga (para el sitio público).
    public static function todosEventos() {
        return Database::connection()->query(self::selectEventos() . ' ORDER BY ev.partido_id, ev.minuto, ev.id')->fetchAll();
    }

    public static function eventoPorId($id) {
        $stmt = Database::connection()->prepare(self::selectEventos() . ' WHERE ev.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function guardarEvento($id, array $d) {
        $pdo = Database::connection();
        if ($id) {
            $pdo->prepare('UPDATE eventos_partido SET equipo_id = ?, jugador_id = ?, asistente_id = ?, tipo = ?, minuto = ? WHERE id = ?')
                ->execute([$d['equipo_id'], $d['jugador_id'], $d['asistente_id'], $d['tipo'], $d['minuto'], $id]);
            return $id;
        }
        $pdo->prepare('INSERT INTO eventos_partido (partido_id, equipo_id, jugador_id, asistente_id, tipo, minuto) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$d['partido_id'], $d['equipo_id'], $d['jugador_id'], $d['asistente_id'], $d['tipo'], $d['minuto']]);
        return (int)$pdo->lastInsertId();
    }

    public static function eliminarEvento($id) {
        Database::connection()->prepare('DELETE FROM eventos_partido WHERE id = ?')->execute([$id]);
    }
}
