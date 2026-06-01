<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Entities\RegistroMaquinaria;
use App\Repositories\MaquinariaRepository;

class MaquinariaController extends Controller
{
    #[Route('/maquinaria/registro', 'POST')]
    public function saveRegistration()
    {
        // Get non-file data
        $operador = $_POST['operador'] ?? '';
        $maquina_id = $_POST['maquina_id'] ?? '';
        $tipo_registro = $_POST['tipo_registro'] ?? '';
        $valor_horometro = (float)($_POST['valor_horometro'] ?? 0);
        $latitud = (float)($_POST['latitud'] ?? 0);
        $longitud = (float)($_POST['longitud'] ?? 0);
        $usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;

        if (empty($operador) || empty($maquina_id) || empty($tipo_registro)) {
            $this->json(['status' => 'error', 'message' => 'Faltan campos obligatorios'], 400);
            return;
        }

        if (!isset($_FILES['foto_horometro']) || $_FILES['foto_horometro']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['status' => 'error', 'message' => 'La foto es obligatoria'], 400);
            return;
        }

        // 1. Create record with empty path first to get ID
        $registro = new RegistroMaquinaria(
            null,
            $operador,
            $maquina_id,
            $tipo_registro,
            $valor_horometro,
            '', // Temporary empty path
            $latitud,
            $longitud,
            null, // fecha_registro
            $usuario_id
        );

        $repo = new MaquinariaRepository();
        $id = $repo->create($registro);

        if (!$id) {
            $this->json(['status' => 'error', 'message' => 'Error al guardar en la base de datos'], 500);
            return;
        }

        // 2. Handle File Upload using a modular folder structure:
        // Path: uploads/registros_maquinaria/{ID}/
        $module_name = 'registros_maquinaria';
        $upload_dir = __DIR__ . "/../../uploads/{$module_name}/{$id}/";
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['foto_horometro']['name'], PATHINFO_EXTENSION);
        $file_name = 'horometro.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['foto_horometro']['tmp_name'], $target_file)) {
            $foto_path = "uploads/{$module_name}/{$id}/" . $file_name;
            
            // 3. Update the database with the final modular path
            $repo->updateFotoPath($id, $foto_path);
            
            $this->json(['status' => 'success', 'message' => 'Registro guardado correctamente', 'id' => $id], 201);
        } else {
            $this->json(['status' => 'error', 'message' => 'Error al organizar la foto'], 500);
        }
    }

    #[Route('/maquinaria/registros', 'GET')]
    public function getAllRegistros()
    {
        $repo = new MaquinariaRepository();
        $registros = $repo->getAllRegistros();
        $this->json(['status' => 'success', 'data' => $registros]);
    }

    #[Route('/maquinaria/registros/update', 'POST')]
    public function updateRegistro()
    {
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        if (empty($data['id'])) {
            $this->json(['status' => 'error', 'message' => 'Falta el ID del registro'], 400);
            return;
        }
        $repo = new MaquinariaRepository();
        $success = $repo->updateRegistro((int)$data['id'], (float)($data['valor_horometro'] ?? 0), $data['tipo_registro'] ?? '');
        if ($success) {
            $this->json(['status' => 'success', 'message' => 'Registro actualizado correctamente']);
        } else {
            $this->json(['status' => 'error', 'message' => 'Error al actualizar el registro'], 500);
        }
    }

    #[Route('/maquinaria/registros/delete', 'POST')]
    public function deleteRegistro()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id'])) {
            $this->json(['status' => 'error', 'message' => 'Falta el ID del registro'], 400);
            return;
        }
        $repo = new MaquinariaRepository();
        $success = $repo->deleteRegistro((int)$data['id']);
        if ($success) {
            $this->json(['status' => 'success', 'message' => 'Registro eliminado permanentemente']);
        } else {
            $this->json(['status' => 'error', 'message' => 'Error al eliminar el registro'], 500);
        }
    }
}
