<?php
namespace Controllers;

use Core\Response;
use Models\EquipoModel;
use Models\JugadorModel;

class EquipoController {
    public function getAll() {
        $equipoModel = new EquipoModel();
        $equipos = $equipoModel->getAll();
        
        $jugadorModel = new JugadorModel();
        // Load players for each team
        foreach ($equipos as &$equipo) {
            $equipo['jugadores'] = $jugadorModel->getByEquipo($equipo['id']);
        }
        
        Response::json($equipos);
    }

    public function getById($params) {
        if (!isset($params['id'])) {
            Response::error('ID no proporcionado', 400);
        }
        $equipoModel = new EquipoModel();
        $equipo = $equipoModel->getById($params['id']);
        if ($equipo) {
            $jugadorModel = new JugadorModel();
            $equipo['jugadores'] = $jugadorModel->getByEquipo($equipo['id']);
            Response::json($equipo);
        } else {
            Response::error('Equipo no encontrado', 404);
        }
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }

        $nombre = $_POST['nombre'] ?? '';
        $representante = $_POST['representante'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $dpi = $_POST['dpi'] ?? '';

        if (empty($nombre) || empty($representante) || empty($telefono) || empty($dpi)) {
            Response::error('Todos los campos son obligatorios', 400);
        }

        if (!isset($_FILES['foto'])) {
            Response::error('La foto (escudo) es obligatoria', 400);
        }

        $equipoModel = new EquipoModel();
        // First insert with empty photo
        $id = $equipoModel->create($nombre, $representante, $telefono, $dpi, '');

        if ($id) {
            // Handle file upload
            $uploadDir = __DIR__ . '/../../uploads/equipos/' . $id . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileInfo = pathinfo($_FILES['foto']['name']);
            $extension = strtolower($fileInfo['extension']);
            $allowed = ['jpg', 'jpeg', 'png'];

            if (!in_array($extension, $allowed)) {
                Response::error('Formato de imagen de escudo no permitido', 400);
            }

            $fileName = 'escudo.' . $extension;
            $destination = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
                $foto_ruta = 'uploads/equipos/' . $id . '/' . $fileName;
                $equipoModel->updateFoto($id, $foto_ruta);
                
                // Handle foto_representante if provided
                if (isset($_FILES['foto_representante'])) {
                    $repInfo = pathinfo($_FILES['foto_representante']['name']);
                    $repExt = strtolower($repInfo['extension']);
                    if (in_array($repExt, $allowed)) {
                        $repFileName = 'representante.' . $repExt;
                        $repDest = $uploadDir . $repFileName;
                        if (move_uploaded_file($_FILES['foto_representante']['tmp_name'], $repDest)) {
                            $foto_rep_ruta = 'uploads/equipos/' . $id . '/' . $repFileName;
                            $equipoModel->updateFotoRepresentante($id, $foto_rep_ruta);
                        }
                    }
                }

                $equipo = $equipoModel->getById($id);
                Response::json(['message' => 'Equipo creado exitosamente', 'data' => $equipo], 201);
            } else {
                Response::error('Error al subir la imagen del escudo', 500);
            }
        } else {
            Response::error('Error al crear el equipo (¿DPI duplicado?)', 500);
        }
    }
}
