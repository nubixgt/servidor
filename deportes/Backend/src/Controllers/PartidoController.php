<?php
namespace Controllers;

use Core\Response;
use Models\PartidoModel;
use Models\EstadisticaModel;
use Utils\JwtUtils;

class PartidoController {
    
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        
        if (!$token) {
            Response::error('No autorizado', 401);
            return;
        }

        $decoded = JwtUtils::verifyToken($token);
        if (!$decoded) {
            Response::error('Token inválido', 401);
            return;
        }
        
        $rol = $decoded['rol'] ?? null;
        $equipo_id_logueado = $decoded['equipo_id'] ?? null;

        if (!isset($data['fecha']) || !isset($data['equipo_local_id']) || !isset($data['equipo_visitante_id']) || !isset($data['estadisticas'])) {
            Response::error('Faltan datos requeridos', 400);
            return;
        }

        // Validación de permisos
        if ($rol !== 'admin') {
            if ($data['equipo_local_id'] != $equipo_id_logueado && $data['equipo_visitante_id'] != $equipo_id_logueado) {
                Response::error('No tienes permiso para registrar este partido', 403);
                return;
            }
        }

        $partidoModel = new PartidoModel();
        $estadisticaModel = new EstadisticaModel();

        // Crear partido
        $partido_id = $partidoModel->create(
            $data['fecha'], 
            $data['equipo_local_id'], 
            $data['equipo_visitante_id'], 
            $data['goles_local'] ?? 0, 
            $data['goles_visitante'] ?? 0, 
            $equipo_id_logueado
        );

        if ($partido_id) {
            // Guardar estadísticas
            if (is_array($data['estadisticas'])) {
                foreach ($data['estadisticas'] as $est) {
                    $estadisticaModel->create(
                        $partido_id,
                        $est['jugador_id'],
                        $est['goles'] ?? 0,
                        $est['tarjetas_amarillas'] ?? 0,
                        $est['tarjetas_rojas'] ?? 0,
                        $est['goles_recibidos'] ?? 0,
                        $est['jugo_como_portero'] ?? false
                    );
                }
            }
            Response::json(['message' => 'Partido registrado exitosamente', 'partido_id' => $partido_id], 201);
        } else {
            Response::error('Error al registrar el partido', 500);
        }
    }

    public function getAll() {
        $partidoModel = new PartidoModel();
        $partidos = $partidoModel->getAll();
        Response::json($partidos);
    }

    public function getById($params) {
        if (!isset($params['id'])) {
            Response::error('ID no proporcionado', 400);
            return;
        }

        $partidoModel = new PartidoModel();
        $partido = $partidoModel->getById($params['id']);
        
        if ($partido) {
            $estadisticaModel = new EstadisticaModel();
            $partido['estadisticas'] = $estadisticaModel->getByPartidoId($partido['id']);
            Response::json($partido);
        } else {
            Response::error('Partido no encontrado', 404);
        }
    }
}
