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

    public function darDeBaja($params) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
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
        $jugador_id = $params['id'] ?? null;
        
        $data = json_decode(file_get_contents('php://input'), true);
        $razon_baja = $data['razon_baja'] ?? '';

        if (!$jugador_id) {
            Response::error('ID de jugador no proporcionado', 400);
        }

        if (empty($razon_baja)) {
            Response::error('La razón de baja es obligatoria', 400);
        }

        $jugadorModel = new JugadorModel();
        $jugador = $jugadorModel->getById($jugador_id);

        if (!$jugador) {
            Response::error('Jugador no encontrado', 404);
        }

        if ($jugador['equipo_id'] != $equipo_id && (!isset($payload['rol']) || $payload['rol'] !== 'admin')) {
            Response::error('No autorizado para modificar este jugador', 403);
        }

        if ($jugadorModel->darDeBaja($jugador_id, $razon_baja)) {
            Response::json(['message' => 'Jugador dado de baja exitosamente']);
        } else {
            Response::error('Error al dar de baja al jugador', 500);
        }
    }

    public function getInactivosByToken() {
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
            $equipo['jugadores'] = $jugadorModel->getByEquipo($equipo_id, 'inactivo');
            Response::json($equipo);
        } else {
            Response::error('Equipo no encontrado', 404);
        }
    }

    public function update($params) {
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

        $jugador_id = $params['id'] ?? null;
        if (!$jugador_id) {
            Response::error('ID de jugador no proporcionado', 400);
        }

        $nombre = $_POST['nombre'] ?? '';
        $dpi = $_POST['dpi'] ?? '';

        if (empty($nombre) || empty($dpi)) {
            Response::error('Nombre y DPI son obligatorios', 400);
        }

        $jugadorModel = new JugadorModel();
        $jugador = $jugadorModel->getById($jugador_id);

        if (!$jugador) {
            Response::error('Jugador no encontrado', 404);
        }

        $equipo_id = $payload['equipo_id'];
        if ($jugador['equipo_id'] != $equipo_id && (!isset($payload['rol']) || $payload['rol'] !== 'admin')) {
            Response::error('No autorizado para modificar este jugador', 403);
        }

        $foto_ruta = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/jugadores/' . $jugador_id . '/';
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
                $foto_ruta = 'uploads/jugadores/' . $jugador_id . '/' . $fileName;
            } else {
                Response::error('Error al subir la imagen', 500);
            }
        }

        if ($jugadorModel->updateJugador($jugador_id, $nombre, $dpi, $foto_ruta)) {
            $updatedJugador = $jugadorModel->getById($jugador_id);
            Response::json(['message' => 'Jugador actualizado exitosamente', 'data' => $updatedJugador]);
        } else {
            Response::error('Error al actualizar el jugador', 500);
        }
    }
}
