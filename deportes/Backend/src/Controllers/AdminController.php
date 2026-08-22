<?php
namespace Controllers;

use Core\Response;
use Models\EquipoModel;
use Models\JugadorModel;
use Models\PartidoModel;
use Utils\JwtUtils;

class AdminController {
    private function verifyAdmin() {
        $token = JwtUtils::getBearerToken();
        if (!$token) {
            Response::error('No autorizado. Token faltante.', 401);
        }
        $payload = JwtUtils::verifyToken($token);
        if (!$payload) {
            Response::error('Token inválido o expirado.', 401);
        }
        if (!isset($payload['rol']) || $payload['rol'] !== 'admin') {
            Response::error('No autorizado. Se requieren permisos de administrador.', 403);
        }
        return $payload;
    }

    public function getEquipos() {
        $this->verifyAdmin();
        
        $equipoModel = new EquipoModel();
        $equipos = $equipoModel->getAll(); // This already filters by 'encargado'

        $jugadorModel = new JugadorModel();
        foreach ($equipos as &$equipo) {
            $jugadores = $jugadorModel->getByEquipo($equipo['id']);
            $equipo['cantidad_jugadores'] = count($jugadores);
        }

        Response::json($equipos);
    }

    public function getEquipoJugadores($params) {
        $this->verifyAdmin();
        
        $equipo_id = $params['id'] ?? null;
        if (!$equipo_id) {
            Response::error('ID de equipo no proporcionado', 400);
        }

        $equipoModel = new EquipoModel();
        $equipo = $equipoModel->getById($equipo_id);
        
        if (!$equipo) {
            Response::error('Equipo no encontrado', 404);
        }

        $jugadorModel = new JugadorModel();
        // Get both active and inactive. Or just all. 
        // We can just fetch all or fetch by status. Let's fetch all.
        // Wait, JugadorModel->getByEquipo has default 'activo'. We need a getAllByEquipo or modify getByEquipo to accept 'todos'.
        
        // Better yet, we can do two calls:
        $equipo['jugadores_activos'] = $jugadorModel->getByEquipo($equipo_id, 'activo');
        $equipo['jugadores_inactivos'] = $jugadorModel->getByEquipo($equipo_id, 'inactivo');
        
        Response::json($equipo);
    }

    public function getEstadisticasGenerales() {
        $this->verifyAdmin();

        $equipoModel = new EquipoModel();
        $jugadorModel = new JugadorModel();
        $partidoModel = new PartidoModel();

        $equiposIncompletos = $equipoModel->getEquiposIncompletos();

        $data = [
            'total_jugadores' => $jugadorModel->countActivos(),
            'total_equipos' => $equipoModel->countEquipos(),
            'equipos_incompletos_count' => count($equiposIncompletos),
            'equipos_incompletos_list' => $equiposIncompletos,
            'total_partidos' => $partidoModel->countPartidos()
        ];

        Response::json($data);
    }

    public function getEncargados() {
        $this->verifyAdmin();
        $equipoModel = new EquipoModel();
        $encargados = $equipoModel->getEncargados();
        Response::json($encargados);
    }
}
