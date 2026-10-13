<?php
namespace Controllers;

use Core\Config;
use Core\Response;
use Models\EquipoModel;
use Models\JugadorModel;
use Models\PartidoModel;

// Datos para el sitio público. Sin sesión: SOLO información pública (nunca DPI, teléfonos, usuarios ni
// datos de representantes) y solo jugadores activos.
class PublicController extends BaseController {
    public function overview() {
        $jugadores = array_filter(JugadorModel::lista(), function ($j) { return $j['estado'] === 'activo'; });

        header('Cache-Control: public, max-age=10');
        Response::json([
            'league' => [
                'name' => Config::get('league_name', 'LIGA PULSE'),
                'season' => Config::get('league_season', 'Apertura 2026'),
                'timezone' => 'America/Guatemala',
            ],
            'teams' => array_map(function ($e) { return EquipoModel::serializar($e, false); }, EquipoModel::todos()),
            'players' => array_values(array_map(function ($j) { return JugadorModel::serializar($j, false); }, $jugadores)),
            'matches' => array_map(['Models\PartidoModel', 'serializar'], PartidoModel::lista()),
            'events' => array_map(['Models\PartidoModel', 'serializarEvento'], PartidoModel::todosEventos()),
        ]);
    }
}
