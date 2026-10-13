<?php
namespace Controllers;

use Core\Database;
use Core\Response;
use Models\JugadorModel;

class PanelController extends BaseController {
    public function resumen() {
        $this->autenticar();
        $pdo = Database::connection();
        $eq = $this->equipoPermitido();

        $contar = function ($sql, $params = []) use ($pdo) {
            $s = $pdo->prepare($sql);
            $s->execute($params);
            return (int)$s->fetchColumn();
        };
        $filtroPartido = $eq ? ' WHERE (equipo_local_id = ? OR equipo_visitante_id = ?)' : '';
        $pPartido = $eq ? [$eq, $eq] : [];

        // Máximos goleadores (solo partidos finalizados), de la liga completa.
        $top = $pdo->query("SELECT j.id, j.nombre, e.nombre AS equipo, COUNT(*) AS goles
                            FROM eventos_partido ev JOIN partidos p ON p.id = ev.partido_id AND p.estado = 'finalizado'
                            JOIN jugadores j ON j.id = ev.jugador_id JOIN equipos e ON e.id = j.equipo_id
                            WHERE ev.tipo = 'gol' GROUP BY j.id, j.nombre, e.nombre ORDER BY goles DESC, j.nombre LIMIT 5")->fetchAll();

        Response::json([
            'teams' => $eq ? 1 : $contar('SELECT COUNT(*) FROM equipos'),
            'players' => $eq ? $contar("SELECT COUNT(*) FROM jugadores WHERE estado = 'activo' AND equipo_id = ?", [$eq]) : $contar("SELECT COUNT(*) FROM jugadores WHERE estado = 'activo'"),
            'matches' => $contar('SELECT COUNT(*) FROM partidos' . $filtroPartido, $pPartido),
            'finished' => $contar("SELECT COUNT(*) FROM partidos" . ($eq ? $filtroPartido . " AND estado = 'finalizado'" : " WHERE estado = 'finalizado'"), $pPartido),
            'top_scorers' => array_map(function ($t) { return ['id' => (int)$t['id'], 'full_name' => $t['nombre'], 'team_name' => $t['equipo'], 'goals' => (int)$t['goles']]; }, $top),
        ]);
    }
}
