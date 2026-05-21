<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\PersonalModel;
use App\Models\DocumentoModel;
use App\Models\MinistroModel;

class FiscalizacionController extends Controller
{
    // --- Endpoints para Personal ---


    #[Route('/fiscalizacion/personal', 'GET')]
    public function getPersonal()
    {
        $model = new PersonalModel();
        try {
            $personal = $model->getAll();
            $this->json([
                'success' => true,
                'data' => $personal
            ]);
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::getPersonal - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en el servidor al obtener el personal'], 500);
        }
    }

    #[Route('/fiscalizacion/personal', 'POST')]
    public function storePersonal()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['nombre']) || empty($data['tipo_puesto']) || empty($data['ministerio_id'])) {
            $this->json(['success' => false, 'error' => 'Faltan campos obligatorios (nombre, tipo_puesto, ministerio_id)'], 400);
            return;
        }

        $model = new PersonalModel();
        try {
            $success = $model->create($data);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Funcionario registrado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo guardar el registro del funcionario'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::storePersonal - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en la base de datos al guardar el funcionario'], 500);
        }
    }

    #[Route('/fiscalizacion/personal/{id}', 'DELETE')]
    public function destroyPersonal($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de registro inválido'], 400);
            return;
        }

        $model = new PersonalModel();
        try {
            $success = $model->delete((int)$id);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Registro de funcionario eliminado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar el funcionario'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::destroyPersonal - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error al intentar eliminar el registro'], 500);
        }
    }

    // --- Endpoints para Ministros Titulares ---

    #[Route('/fiscalizacion/ministro-fotos', 'GET')]
    public function getMinistroFotos()
    {
        $model = new MinistroModel();
        try {
            $ministros = $model->getAll();
            $data = [];
            foreach ($ministros as $m) {
                $data[$m['ministerio_id']] = [
                    'nombre' => $m['nombre_ministro'],
                    'foto' => $m['foto_url']
                ];
            }
            $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::getMinistroFotos - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en el servidor al obtener las fotos de ministros'], 500);
        }
    }

    #[Route('/fiscalizacion/ministro-foto/{id}', 'POST')]
    #[Authorize(['administrador'])]
    public function storeMinistroFoto($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de ministerio inválido'], 400);
            return;
        }

        if (empty($_FILES['foto'])) {
            $this->json(['success' => false, 'error' => 'No se subió ninguna imagen'], 400);
            return;
        }

        $file = $_FILES['foto'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'error' => 'Error al subir el archivo'], 400);
            return;
        }

        // Validar formato
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['success' => false, 'error' => 'Formato de imagen inválido. Solo JPG, PNG o WebP.'], 400);
            return;
        }

        $targetDir = __DIR__ . '/../../uploads/ministros/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = "ministro_" . $id . "." . $ext;
        $targetFile = $targetDir . $fileName;
        $fotoUrl = "/uploads/ministros/" . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $model = new MinistroModel();
            try {
                $model->upsertFoto((int)$id, $fotoUrl);
                $this->json([
                    'success' => true,
                    'url' => $fotoUrl
                ]);
            } catch (\Exception $e) {
                error_log("Error al guardar foto en BD: " . $e->getMessage());
                $this->json(['success' => false, 'error' => 'Error en la base de datos al registrar la foto'], 500);
            }
        } else {
            $this->json(['success' => false, 'error' => 'Error al mover el archivo subido al servidor'], 500);
        }
    }

    #[Route('/fiscalizacion/ministro-foto/{id}', 'DELETE')]
    #[Authorize(['administrador'])]
    public function destroyMinistroFoto($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de ministerio inválido'], 400);
            return;
        }

        $model = new MinistroModel();
        try {
            $ministros = $model->getAll();
            $currentFoto = null;
            foreach ($ministros as $m) {
                if ($m['ministerio_id'] == $id) {
                    $currentFoto = $m['foto_url'];
                    break;
                }
            }
            if ($currentFoto) {
                $filePath = __DIR__ . '/../../' . ltrim($currentFoto, '/');
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }
            }

            $success = $model->deleteFoto((int)$id);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Foto de ministro eliminada exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar la foto de la BD'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::destroyMinistroFoto - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error al intentar eliminar la foto del ministro'], 500);
        }
    }

    #[Route('/fiscalizacion/ministro-nombre/{id}', 'POST')]
    #[Authorize(['administrador'])]
    public function storeMinistroNombre($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de ministerio inválido'], 400);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!isset($data['nombre'])) {
            $this->json(['success' => false, 'error' => 'Nombre del ministro es requerido'], 400);
            return;
        }

        $nombre = trim($data['nombre']);
        if ($nombre === '') {
            $nombre = 'Pendiente';
        }

        $model = new MinistroModel();
        try {
            $success = $model->upsertNombre((int)$id, $nombre);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Nombre del ministro actualizado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo actualizar el nombre del ministro'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::storeMinistroNombre - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en la base de datos al actualizar el nombre del ministro'], 500);
        }
    }

    // --- Endpoints para Documentos ---

    #[Route('/fiscalizacion/documentos', 'GET')]
    public function getDocumentos()
    {
        $model = new DocumentoModel();
        try {
            $documentos = $model->getAll();
            $this->json([
                'success' => true,
                'data' => $documentos
            ]);
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::getDocumentos - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en el servidor al obtener los documentos'], 500);
        }
    }

    #[Route('/fiscalizacion/documentos', 'POST')]
    public function storeDocumento()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['nombre']) || empty($data['tipo']) || empty($data['entidad'])) {
            $this->json(['success' => false, 'error' => 'Faltan campos obligatorios (nombre, tipo, entidad)'], 400);
            return;
        }

        $data['fecha'] = !empty($data['fecha']) ? $data['fecha'] : date('Y-m-d');

        $model = new DocumentoModel();
        try {
            $success = $model->create($data);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Documento guardado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo registrar el documento'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::storeDocumento - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en la base de datos al guardar el documento'], 500);
        }
    }

    #[Route('/fiscalizacion/documentos/{id}', 'DELETE')]
    public function destroyDocumento($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de documento inválido'], 400);
            return;
        }

        $model = new DocumentoModel();
        try {
            $success = $model->delete((int)$id);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Documento eliminado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar el documento'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en FiscalizacionController::destroyDocumento - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error al intentar eliminar el documento'], 500);
        }
    }
}
