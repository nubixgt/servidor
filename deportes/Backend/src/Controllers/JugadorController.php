<?php
namespace Controllers;

use Core\Response;
use Models\JugadorModel;
use Models\EquipoModel;
use Utils\JwtUtils;

class JugadorController {
    public function getByToken() {
        $token = JwtUtils::getBearerToken();
        if (!$token) {
            Response::error('No autorizado. Token faltante.', 401);
        }
        $payload = JwtUtils::verifyToken($token);
        if (!$payload) {
            Response::error('Token inválido o expirado.', 401);
        }

        $equipo_id = $payload['equipo_id'];
        
        $equipoModel = new EquipoModel();
        $equipo = $equipoModel->getById($equipo_id);
        
        if ($equipo) {
            $jugadorModel = new JugadorModel();
            $equipo['jugadores'] = $jugadorModel->getByEquipo($equipo_id);
            Response::json($equipo);
        } else {
            Response::error('Equipo no encontrado', 404);
        }
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }

        $token = JwtUtils::getBearerToken();
        if (!$token) {
            Response::error('No autorizado. Token faltante.', 401);
        }
        $payload = JwtUtils::verifyToken($token);
        if (!$payload) {
            Response::error('Token inválido o expirado.', 401);
        }

        $equipo_id = $payload['equipo_id'];
        $nombre = $_POST['nombre'] ?? '';
        $dpi = $_POST['dpi'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $posicion = $_POST['posicion'] ?? '';

        if (empty($equipo_id) || empty($nombre) || empty($dpi) || empty($telefono)) {
            Response::error('Todos los campos son obligatorios', 400);
        }

        if (!isset($_FILES['foto'])) {
            Response::error('La foto es obligatoria', 400);
        }

        $jugadorModel = new JugadorModel();
        // First insert with empty photo
        $id = $jugadorModel->create($equipo_id, $nombre, $dpi, '', $telefono, $posicion);

        if ($id) {
            // Handle file upload
            $uploadDir = __DIR__ . '/../../uploads/jugadores/' . $id . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileInfo = pathinfo($_FILES['foto']['name']);
            $extension = strtolower($fileInfo['extension']);
            $allowed = ['jpg', 'jpeg', 'png'];

            if (!in_array($extension, $allowed)) {
                Response::error('Formato de imagen no permitido', 400);
            }

            $fileName = 'foto.' . $extension;
            $destination = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
                $foto_ruta = 'uploads/jugadores/' . $id . '/' . $fileName;
                $jugadorModel->updateFoto($id, $foto_ruta);
                
                Response::json(['message' => 'Jugador creado exitosamente'], 201);
            } else {
                Response::error('Error al subir la imagen', 500);
            }
        } else {
            Response::error('Error al crear el jugador (¿DPI duplicado?)', 500);
        }
    }
}
