<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Models\PersonalModel;
use App\Models\DocumentoModel;

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
